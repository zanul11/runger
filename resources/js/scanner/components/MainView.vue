<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { state, canTiming, canRacepack } from '../store.js';
import { syncSoon } from '../sync.js';
import Icon from './Icon.vue';
import MenuSheet from './MenuSheet.vue';
import InstallCard from './InstallCard.vue';
import TimingPanel from './TimingPanel.vue';
import RacepackPanel from './RacepackPanel.vue';
import ParticipantsView from './ParticipantsView.vue';

const point = computed(() => state.session.timing_point);
const modes = computed(() => [
    ...(canTiming() ? ['timing'] : []),
    ...(canRacepack() ? ['racepack'] : []),
]);
const mode = ref(null);
const view = ref('main');   // main | participants
const menu = ref(false);
const toast = ref(null);
let toastTimer;
let wakeLock = null;

// Mode awal sesuai peran; admin bisa berpindah lewat tombol di bawah lokasi.
watch(modes, (m) => {
    if (!m.includes(mode.value)) mode.value = m[0] ?? null;
}, { immediate: true });

function notify(msg) {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 3000);
}

// Layar tetap menyala selama scanner terbuka (dipakai lama di luar ruangan).
async function keepAwake() {
    try {
        if (document.visibilityState === 'visible') wakeLock = await navigator.wakeLock?.request('screen');
    } catch {
        wakeLock = null;
    }
}
onMounted(() => {
    keepAwake();
    document.addEventListener('visibilitychange', keepAwake);
});
onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', keepAwake);
    wakeLock?.release?.();
});

const initials = computed(() => (state.session.user.name || '?').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase());

const syncPill = computed(() => {
    if (!state.online) return { cls: 'off', label: 'Offline' };
    if (state.pending > 0) return { cls: 'queue' + (state.syncing ? ' syncing' : ''), label: 'Antre' };
    return { cls: state.syncing ? 'syncing' : '', label: 'Terkirim' };
});

function openParticipants() {
    menu.value = false;
    view.value = 'participants';
}
</script>

<template>
    <div class="shell">
        <!-- Baris 1: aplikasi, status kirim, akun -->
        <header class="top">
            <div class="brandmark">
                <span class="mark"><Icon name="flag" :size="20" /></span>
                <span>GTR Scanner<small>Gerung Trail Run 2026</small></span>
            </div>
            <button class="pill" :class="syncPill.cls" :aria-label="`${syncPill.label}, ${state.pending} data menunggu`" @click="syncSoon(0)">
                <span class="dot"></span>
                <span>{{ syncPill.label }}</span>
                <b v-if="state.pending > 0" class="num">{{ state.pending }}</b>
            </button>
            <button class="avatar" aria-label="Menu" @click="menu = true">{{ initials }}</button>
        </header>

        <!-- Baris 2: lokasi — lebar penuh, nama boleh 2 baris supaya selalu terbaca -->
        <section class="locbar" :class="mode === 'racepack' ? 'rp' : ''">
            <template v-if="mode === 'timing'">
                <div class="loc-badge">{{ point?.code ?? '—' }}</div>
                <div class="loc-text">
                    <small>Pos timing</small>
                    <strong>{{ point?.name ?? 'Belum ditugaskan' }}</strong>
                </div>
            </template>
            <template v-else>
                <div class="loc-badge"><Icon name="package" :size="26" /></div>
                <div class="loc-text">
                    <small>Pengambilan</small>
                    <strong>Race Pack</strong>
                </div>
            </template>
            <button class="loc-people" :class="{ empty: state.rosterCount === 0 }" aria-label="Lihat data peserta" @click="openParticipants">
                <Icon name="users" :size="20" />
                <b class="num">{{ state.rosterCount }}</b>
                <small>peserta</small>
            </button>
        </section>

        <InstallCard banner @notify="notify" />

        <ParticipantsView v-if="view === 'participants'" :mode="mode" @back="view = 'main'" @notify="notify" />

        <template v-else>
            <div v-if="modes.length > 1" class="mode-bar">
                <div class="seg" role="tablist">
                    <button role="tab" :class="{ on: mode === 'timing' }" :aria-selected="mode === 'timing'" @click="mode = 'timing'">
                        <Icon name="timer" :size="20" /> Timing
                    </button>
                    <button role="tab" :class="{ on: mode === 'racepack' }" :aria-selected="mode === 'racepack'" @click="mode = 'racepack'">
                        <Icon name="package" :size="20" /> Race Pack
                    </button>
                </div>
            </div>

            <div v-if="state.lastError && !state.authExpired" class="panel" style="flex:none;padding-bottom:0">
                <div class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ state.lastError }} Data tersimpan di HP dan dikirim otomatis.</span></div>
            </div>
            <div v-if="state.rosterCount === 0" class="panel" style="flex:none;padding-bottom:0">
                <button class="alert bad" style="text-align:left;width:100%" @click="openParticipants">
                    <Icon name="alert" :size="20" /><span>Data peserta belum terunduh di HP ini. Ketuk untuk mengunduh.</span>
                </button>
            </div>

            <TimingPanel v-if="mode === 'timing' && point" />
            <div v-else-if="mode === 'timing'" class="panel">
                <div class="stage"><div class="stage-idle"><Icon name="map-off" :size="44" /><span class="hint">Akun ini belum ditugaskan ke pos timing.<br>Hubungi admin.</span></div></div>
            </div>
            <RacepackPanel v-else-if="mode === 'racepack'" />
            <div v-else class="panel">
                <div class="alert bad"><Icon name="lock" :size="20" /><span>Akun ini tidak punya akses scanner.</span></div>
            </div>
        </template>

        <MenuSheet v-if="menu" @close="menu = false" @notify="notify" @participants="openParticipants" />
        <div v-if="toast" class="toast" role="status">{{ toast }}</div>
    </div>
</template>
