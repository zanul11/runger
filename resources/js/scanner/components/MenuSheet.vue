<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { state, logout, refreshRoster } from '../store.js';
import { syncSoon } from '../sync.js';
import { formatTime, nowMs } from '../clock.js';
import Icon from './Icon.vue';
import InstallCard from './InstallCard.vue';

const emit = defineEmits(['close', 'notify', 'participants']);
const busy = ref(false);
const now = ref(nowMs());
let tick;
onMounted(() => { tick = setInterval(() => { now.value = nowMs(); }, 1000); });
onBeforeUnmount(() => clearInterval(tick));

const ROLES = { marshal: 'Marshal timing', racepack: 'Petugas race pack', admin: 'Admin' };
const user = computed(() => state.session.user);
const initials = computed(() => (user.value.name || '?').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase());
const offset = computed(() => {
    const ms = state.clock.offsetMs ?? 0;
    return Math.abs(ms) < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} dtk`;
});

async function reload() {
    busy.value = true;
    try {
        await refreshRoster();
        emit('notify', `Data peserta diperbarui (${state.rosterCount})`);
        emit('close');
    } catch (e) {
        emit('notify', navigator.onLine ? (e.message || 'Gagal memuat data') : 'Offline — memakai data peserta terakhir');
    } finally {
        busy.value = false;
    }
}

function syncNow() {
    syncSoon(0);
    emit('notify', state.online ? 'Mengirim data…' : 'Offline — data dikirim saat ada sinyal');
    emit('close');
}

async function doLogout() {
    const msg = state.pending > 0
        ? `Masih ada ${state.pending} data BELUM terkirim. Data tetap aman di HP ini dan terkirim saat Anda login lagi. Tetap keluar?`
        : 'Keluar dari scanner?';
    if (confirm(msg)) {
        await logout();
        emit('close');
    }
}
</script>

<template>
    <div class="scrim" @click="emit('close')"></div>
    <section class="sheet" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="sheet-grip"></div>

        <div class="sheet-user">
            <div class="avatar">{{ initials }}</div>
            <div class="sheet-who">
                <b>{{ user.name }}</b>
                <span class="role">{{ ROLES[user.role] ?? user.role }}</span>
            </div>
            <button class="round" aria-label="Tutup menu" @click="emit('close')"><Icon name="x" :size="20" :stroke="2.5" /></button>
        </div>

        <div class="stats">
            <div class="stat dark"><small>Jam (WITA)</small><b class="num">{{ formatTime(now) }}</b></div>
            <div class="stat" :class="{ hot: state.pending > 0 }"><small>Belum terkirim</small><b class="num">{{ state.pending }}</b></div>
            <div class="stat"><small>Data peserta</small><b class="num">{{ state.rosterCount }}</b></div>
            <div class="stat"><small>Selisih jam HP</small><b class="num">{{ offset }}</b></div>
            <div class="stat wide">
                <small>Sinkron terakhir</small>
                <span><b class="num">{{ state.lastSyncAt ? formatTime(state.lastSyncAt) : '—' }}</b><span class="muted"> · {{ state.online ? 'online' : 'offline' }}</span></span>
            </div>
        </div>

        <div class="tiles">
            <button class="tile" @click="emit('participants')">
                <span class="tile-ic"><Icon name="users" :size="18" /></span>
                <span>Data peserta<small class="num">{{ state.rosterCount }} di HP</small></span>
            </button>
            <button class="tile" @click="syncNow">
                <span class="tile-ic"><Icon name="upload" :size="18" /></span>
                <span>Kirim data<small>sekarang</small></span>
            </button>
            <button class="tile" :disabled="busy" @click="reload">
                <span class="tile-ic"><Icon name="refresh" :size="18" /></span>
                <span>{{ busy ? 'Memuat…' : 'Muat ulang' }}<small>data peserta</small></span>
            </button>
            <button class="tile danger" @click="doLogout">
                <span class="tile-ic"><Icon name="logout" :size="18" /></span>
                <span>Keluar<small>dari scanner</small></span>
            </button>
        </div>

        <InstallCard class="sheet-install" @notify="emit('notify', $event)" />

        <p class="device">Device {{ state.session.device_id?.slice(0, 8) }}</p>
    </section>
</template>
