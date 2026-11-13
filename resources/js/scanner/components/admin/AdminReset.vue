<script setup>
import { onMounted, ref } from 'vue';
import { adminApi, refreshRoster } from '../../store.js';
import { formatTime } from '../../clock.js';
import Icon from '../Icon.vue';

/**
 * Reset data uji coba (sama dengan `php artisan gtr:reset-race-day`), dikonfirmasi dengan kode
 * reset (GTR_RESET_CODE di server). HP ini tetap login; antrean lokal lama ikut dibersihkan.
 */
const emit = defineEmits(['back', 'notify']);
const preview = ref(null);
const error = ref(null);
const code = ref('');
const racepack = ref(false);
const logout = ref(false);
const busy = ref(false);
const done = ref(null);

async function loadPreview() {
    try {
        preview.value = await adminApi('/reset');
        error.value = null;
    } catch (e) {
        error.value = e.message || 'Gagal memuat';
    }
}
onMounted(loadPreview);

const ALWAYS = ['Scan BIB', 'Hasil', 'Koreksi hasil', 'Kategori sudah di-START', 'Status lomba peserta'];

async function run() {
    if (!code.value.trim()) return;
    busy.value = true;
    error.value = null;
    try {
        const res = await adminApi('/reset', { method: 'POST', body: { code: code.value.trim(), racepack: racepack.value, logout: logout.value } });
        done.value = res;
        code.value = '';
        await refreshRoster().catch(() => {}); // HP ini: hapus antrean & riwayat lokal sebelum reset
        emit('notify', 'Data uji dihapus.');
        await loadPreview();
    } catch (e) {
        error.value = e.status === 429 ? 'Terlalu banyak percobaan. Tunggu 1 menit.' : (e.message || 'Gagal reset');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Reset Data Uji</h2>
        </div>

        <div class="alert bad"><Icon name="alert" :size="20" /><span>Menghapus <b>semua</b> scan, hasil, koreksi, dan gun time START. Hanya untuk membersihkan uji coba — <b>jangan dipakai saat race berjalan.</b></span></div>
        <div v-if="error" class="alert warn"><Icon name="alert" :size="20" /><span>{{ error }}</span></div>
        <div v-if="done" class="alert"><Icon name="check-circle" :size="20" /><span>Reset selesai {{ formatTime(done.reset_at_ms) }} WITA. Scan dari HP yang dibuat sebelum jam ini ditolak server.</span></div>

        <template v-if="preview">
            <div v-if="!preview.enabled" class="alert warn"><Icon name="lock" :size="20" /><span>Reset dari aplikasi belum aktif. Isi <b>GTR_RESET_CODE</b> di .env server, atau pakai <b>php artisan gtr:reset-race-day</b>.</span></div>

            <section class="acard">
                <div class="section-title"><span>Yang akan dihapus</span></div>
                <div class="kv">
                    <template v-for="k in ALWAYS" :key="k"><span>{{ k }}</span><b class="num">{{ preview.counts[k] }}</b></template>
                </div>
                <label class="toggle">
                    <input v-model="racepack" type="checkbox">
                    <span class="grow">Check-in racepack <small>{{ preview.counts['Check-in racepack'] }} peserta jadi "belum ambil"</small></span>
                </label>
                <label class="toggle">
                    <input v-model="logout" type="checkbox">
                    <span class="grow">Logout HP lain <small>{{ preview.counts['Login HP scanner'] }} login · HP ini tetap masuk</small></span>
                </label>
                <p class="note">Peserta, pembayaran, BIB, kategori, pos & penugasan marshal tidak disentuh. Data yang dihapus disalin dulu ke server (storage/app/private/gtr-reset).</p>
                <p v-if="preview.last_reset_ms" class="note">Reset terakhir: {{ new Date(preview.last_reset_ms).toLocaleString('id-ID', { timeZone: 'Asia/Makassar' }) }} WITA</p>
            </section>

            <label class="field">
                <span>Kode reset</span>
                <div class="input">
                    <Icon name="lock" :size="20" />
                    <input v-model="code" type="password" autocomplete="off" placeholder="Masukkan kode" :disabled="!preview.enabled" @keydown.enter="run">
                </div>
            </label>
            <button class="btn block lg danger-solid" :disabled="busy || !preview.enabled || !code.trim()" @click="run">
                <Icon name="trash" :size="20" /> {{ busy ? 'Menghapus…' : 'Hapus data uji' }}
            </button>
        </template>
        <p v-else-if="!error" class="empty">Memuat…</p>
    </main>
</template>
