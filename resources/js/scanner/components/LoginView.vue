<script setup>
import { ref } from 'vue';
import { login, state } from '../store.js';
import { unlockAudio } from '../feedback.js';
import Icon from './Icon.vue';
import InstallCard from './InstallCard.vue';
import gtrLogo from '../assets/gtr-logo.png';
import rungerLogo from '../assets/gtr-logo-dark.png';

const username = ref(state.session?.user?.username ?? '');
const password = ref('');
const showPassword = ref(false);
const busy = ref(false);
const error = ref(null);

async function submit() {
    unlockAudio();
    error.value = null;
    if (!navigator.onLine) {
        error.value = 'Login butuh internet. Setelah login, scanner tetap jalan tanpa sinyal.';
        return;
    }
    busy.value = true;
    try {
        await login(username.value.trim(), password.value);
        password.value = '';
    } catch (e) {
        error.value = e.status === 422 ? 'Username atau password salah.' : (e.message || 'Gagal login.');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <main class="login">
        <section class="login-brand">
            <div class="login-logos">
                <span class="logo-tile"><img :src="gtrLogo" alt="Gerung Trail Run — Keteri Hill"></span>
                <img class="runger" :src="rungerLogo" alt="Runger — Runners Gerung">
            </div>
            <h1>Halo, panitia!<br>Siap scan?</h1>
            <p>Timing &amp; Race Pack · Gerung Trail Run 2026</p>
        </section>

        <div v-if="state.authExpired" class="alert warn">
            <Icon name="alert" :size="20" />
            <span>Sesi habis — login ulang. Data yang belum terkirim tetap aman di HP ini ({{ state.pending }}).</span>
        </div>

        <form @submit.prevent="submit">
            <label class="field">
                <span>Username</span>
                <div class="input">
                    <Icon name="user" :size="20" />
                    <input v-model="username" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false" required>
                </div>
            </label>
            <label class="field">
                <span>Password</span>
                <div class="input">
                    <Icon name="lock" :size="20" />
                    <input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required>
                    <button type="button" class="eye" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">
                        <Icon :name="showPassword ? 'eye-off' : 'eye'" :size="20" />
                    </button>
                </div>
            </label>

            <div v-if="error" class="alert bad"><Icon name="x-circle" :size="20" /><span>{{ error }}</span></div>

            <button class="btn success lg block" :disabled="busy">{{ busy ? 'Masuk…' : 'Masuk' }} <span class="go"><Icon name="chevron-right" :size="20" :stroke="2.75" /></span></button>
        </form>

        <InstallCard />

        <p class="login-foot">Akun dibuat panitia di panel admin (Marshal / Petugas Race Pack).</p>
    </main>
</template>
