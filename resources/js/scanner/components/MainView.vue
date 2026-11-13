<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { state, canTiming, canRacepack, canAdmin } from '../store.js';
import { syncSoon } from '../sync.js';
import Icon from './Icon.vue';
import MenuSheet from './MenuSheet.vue';
import InstallCard from './InstallCard.vue';
import TimingPanel from './TimingPanel.vue';
import RacepackPanel from './RacepackPanel.vue';
import gtrLogo from '../assets/gtr-logo.png';
import ParticipantsView from './ParticipantsView.vue';
import AdminPanel from './admin/AdminPanel.vue';
import LocationPicker from './admin/LocationPicker.vue';

const point = computed(() => state.session.timing_point);
// Admin: Kontrol + Scan (lokasi dipilih dulu). Marshal: timing. Petugas racepack: race pack.
const modes = computed(() => (canAdmin() ? ['admin', 'scan'] : [
    ...(canTiming() ? ['timing'] : []),
    ...(canRacepack() ? ['racepack'] : []),
]));
const mode = ref(null);
/** Mode yang benar-benar tampil: tab Scan admin = timing / racepack sesuai lokasi pilihan (null = belum pilih). */
const active = computed(() => {
    if (mode.value !== 'scan') return mode.value;
    if (state.adminLocation === 'racepack') return 'racepack';
    return state.adminLocation != null && point.value?.id === state.adminLocation ? 'timing' : null;
});
const scanLabel = computed(() => (active.value === 'racepack' ? 'Race Pack' : active.value === 'timing' ? point.value?.code : null));
const MODE_LABEL = { timing: 'Timing', racepack: 'Race Pack', admin: 'Kontrol' };
const view = ref('main');   // main | participants
const menu = ref(false);
const toast = ref(null);
let toastTimer;
let wakeLock = null;

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
        <!-- Header tetap (tidak ikut scroll): akun, status kirim, lokasi -->
        <header class="top">
            <span class="logo-tile sm"><img :src="gtrLogo" alt="Gerung Trail Run"></span>
            <div class="brandmark">
                <b>Scanner</b>
                <small>{{ mode === 'scan' && !active ? 'Pilih lokasi' : (MODE_LABEL[active] ?? 'Timing') }} · GTR 2026</small>
            </div>
            <button class="pill" :class="syncPill.cls" :aria-label="`${syncPill.label}, ${state.pending} data menunggu`" @click="syncSoon(0)">
                <span class="dot"></span>
                <span>{{ syncPill.label }}</span>
                <b v-if="state.pending > 0" class="num">{{ state.pending }}</b>
            </button>
            <button class="avatar" aria-label="Menu" @click="menu = true">{{ initials }}</button>
        </header>

        <!-- Lokasi — nama boleh 2 baris supaya selalu terbaca -->
        <section v-if="active === 'admin' || (mode === 'scan' && !active)" class="locbar rp">
            <div class="loc-badge"><Icon name="grid" :size="24" /></div>
            <div class="loc-text">
                <small>Admin</small>
                <strong>{{ active === 'admin' ? 'Kontrol Race Day' : 'Pilih lokasi scan' }}</strong>
            </div>
            <button class="loc-people" :class="{ empty: state.rosterCount === 0 }" aria-label="Lihat data peserta" @click="openParticipants">
                <Icon name="users" :size="18" />
                <b class="num">{{ state.rosterCount }}</b>
            </button>
        </section>
        <section v-else class="locbar" :class="active === 'racepack' ? 'rp' : ''">
            <template v-if="active === 'timing'">
                <div class="loc-badge">{{ point?.code ?? '—' }}</div>
                <div class="loc-text">
                    <small>Pos timing</small>
                    <strong>{{ point?.name ?? 'Belum ditugaskan' }}</strong>
                </div>
            </template>
            <template v-else>
                <div class="loc-badge"><Icon name="package" :size="24" /></div>
                <div class="loc-text">
                    <small>Pengambilan</small>
                    <strong>Race Pack</strong>
                </div>
            </template>
            <button v-if="mode === 'scan'" class="loc-people loc-change" aria-label="Ganti lokasi scan" @click="state.adminLocation = null">
                <Icon name="pin" :size="18" /> Ganti
            </button>
            <button class="loc-people" :class="{ empty: state.rosterCount === 0 }" aria-label="Lihat data peserta" @click="openParticipants">
                <Icon name="users" :size="18" />
                <b class="num">{{ state.rosterCount }}</b>
            </button>
        </section>

        <InstallCard banner @notify="notify" />

        <div v-if="modes.length > 1 && view === 'main'" class="mode-bar">
            <div class="seg" role="tablist">
                <template v-if="modes.includes('admin')">
                    <button role="tab" :class="{ on: mode === 'admin' }" :aria-selected="mode === 'admin'" @click="mode = 'admin'">
                        <Icon name="grid" :size="18" /> Kontrol
                    </button>
                    <button role="tab" :class="{ on: mode === 'scan' }" :aria-selected="mode === 'scan'" @click="mode = 'scan'">
                        <Icon name="camera" :size="18" /> Scan<template v-if="scanLabel"> · {{ scanLabel }}</template>
                    </button>
                </template>
                <template v-else>
                    <button role="tab" :class="{ on: mode === 'timing' }" :aria-selected="mode === 'timing'" @click="mode = 'timing'">
                        <Icon name="timer" :size="18" /> Timing
                    </button>
                    <button role="tab" :class="{ on: mode === 'racepack' }" :aria-selected="mode === 'racepack'" @click="mode = 'racepack'">
                        <Icon name="package" :size="18" /> Race Pack
                    </button>
                </template>
            </div>
        </div>

        <ParticipantsView v-if="view === 'participants'" :mode="active === 'timing' ? 'timing' : 'racepack'" @back="view = 'main'" @notify="notify" />

        <template v-else>
            <div v-if="(state.lastError && !state.authExpired) || state.rosterCount === 0" class="notices">
                <div v-if="state.lastError && !state.authExpired" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ state.lastError }} Data tersimpan di HP dan dikirim otomatis.</span></div>
                <button v-if="state.rosterCount === 0" class="alert bad" @click="openParticipants">
                    <Icon name="alert" :size="20" /><span>Data peserta belum terunduh di HP ini. Ketuk untuk mengunduh.</span>
                </button>
            </div>

            <LocationPicker v-if="mode === 'scan' && !active" @notify="notify" />
            <TimingPanel v-else-if="active === 'timing' && point" />
            <div v-else-if="active === 'timing'" class="panel">
                <div class="stage"><div class="stage-idle"><Icon name="map-off" :size="40" /><span class="hint">Akun ini belum ditugaskan ke pos timing.<br>Hubungi admin.</span></div></div>
            </div>
            <RacepackPanel v-else-if="active === 'racepack'" />
            <AdminPanel v-else-if="active === 'admin'" @notify="notify" />
            <div v-else class="panel">
                <div class="alert bad"><Icon name="lock" :size="20" /><span>Akun ini tidak punya akses scanner.</span></div>
            </div>
        </template>

        <MenuSheet v-if="menu" @close="menu = false" @notify="notify" @participants="openParticipants" />
        <div v-if="toast" class="toast" role="status">{{ toast }}</div>
    </div>
</template>
