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
            <div>
                <b>{{ user.name }}</b>
                <span class="role">{{ ROLES[user.role] ?? user.role }}</span>
            </div>
        </div>

        <div class="stats">
            <div class="stat"><small>Jam (WITA)</small><b class="num">{{ formatTime(now) }}</b></div>
            <div class="stat"><small>Belum terkirim</small><b class="num">{{ state.pending }}</b></div>
            <div class="stat"><small>Data peserta</small><b class="num">{{ state.rosterCount }}</b></div>
            <div class="stat"><small>Selisih jam HP</small><b class="num">{{ offset }}</b></div>
            <div class="stat" style="grid-column: span 2">
                <small>Sinkron terakhir</small>
                <b class="num">{{ state.lastSyncAt ? formatTime(state.lastSyncAt) : '—' }}</b>
                <span class="muted"> · {{ state.online ? 'online' : 'offline' }}</span>
            </div>
        </div>

        <InstallCard style="margin-bottom: 14px" @notify="emit('notify', $event)" />

        <div class="actions">
            <button class="btn block" @click="emit('participants')"><Icon name="users" :size="22" /> Data peserta ({{ state.rosterCount }})</button>
            <button class="btn block" @click="syncNow"><Icon name="upload" :size="22" /> Kirim data sekarang</button>
            <button class="btn block" :disabled="busy" @click="reload"><Icon name="refresh" :size="22" /> {{ busy ? 'Memuat…' : 'Muat ulang data peserta' }}</button>
            <button class="btn danger block" @click="doLogout"><Icon name="logout" :size="22" /> Keluar</button>
            <button class="btn ghost block" @click="emit('close')">Tutup</button>
        </div>

        <p class="device">Device {{ state.session.device_id?.slice(0, 8) }}</p>
    </section>
</template>
