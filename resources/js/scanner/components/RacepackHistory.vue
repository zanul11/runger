<script setup>
import { computed, ref } from 'vue';
import { racepackHistory, racepackCancel, canAdmin } from '../store.js';
import { formatDayTime } from '../clock.js';
import { usePoll } from '../admin.js';
import Icon from './Icon.vue';

/** Riwayat pengambilan race pack: waktu, petugas, dan wakil bila diwakilkan. */
const emit = defineEmits(['back', 'notify']);
const { data, error, loading, load } = usePoll(racepackHistory, 30_000);

const q = ref('');
const onlyProxy = ref(false);
const fold = (s) => String(s ?? '').toLowerCase();
const items = computed(() => (data.value?.items ?? []).filter((r) => (!onlyProxy.value || r.picked_by)
    && (!q.value.trim() || [r.name, r.bib, r.registration_number, r.picked_by, r.collected_by].some((v) => fold(v).includes(fold(q.value.trim()))))));
const proxies = computed(() => (data.value?.items ?? []).filter((r) => r.picked_by && !r.cancelled_at_ms).length);
const cancelled = computed(() => (data.value?.items ?? []).filter((r) => r.cancelled_at_ms).length);

const busy = ref(null);
async function cancel(r) {
    if (!confirm(`Batalkan pengambilan race pack ${r.name} (BIB ${r.bib ?? '—'})? Tetap tercatat di riwayat.`)) return;
    busy.value = r.id;
    try {
        await racepackCancel(r.registration_number);
        emit('notify', `Pengambilan ${r.name} dibatalkan`);
    } catch (e) {
        emit('notify', e.body?.status === 'not_collected' ? 'Sudah dibatalkan sebelumnya' : (e.message || 'Gagal membatalkan'));
    } finally {
        busy.value = null;
        load();
    }
}
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Riwayat Race Pack</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <div class="stats three">
                <div class="stat dark"><small>Diambil</small><b class="num">{{ data.collected }}/{{ data.total }}</b></div>
                <div class="stat"><small>Diwakilkan</small><b class="num">{{ proxies }}</b></div>
                <div class="stat" :class="{ hot: cancelled }"><small>Dibatalkan</small><b class="num">{{ cancelled }}</b></div>
            </div>

            <div class="input search">
                <Icon name="search" :size="20" />
                <input v-model="q" type="search" autocomplete="off" placeholder="Nama / BIB / wakil / petugas" aria-label="Cari riwayat">
                <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
            </div>
            <div class="chips">
                <button :class="{ on: !onlyProxy }" @click="onlyProxy = false">Semua</button>
                <button :class="{ on: onlyProxy }" @click="onlyProxy = true">Diwakilkan <b class="num">{{ proxies }}</b></button>
            </div>

            <ul v-if="items.length" class="alist">
                <li v-for="r in items" :key="r.id" :style="r.cancelled_at_ms ? 'opacity:.6' : null">
                    <b class="bib num">{{ r.bib ?? '—' }}</b>
                    <div class="grow">
                        <b>{{ r.name }}</b>
                        <small>{{ r.category }}<template v-if="r.size"> · Jersey {{ r.size }}</template> · petugas {{ r.collected_by ?? '—' }}</small>
                        <small v-if="r.picked_by" class="hot"><Icon name="user" :size="14" /> Diwakilkan: {{ r.picked_by }}</small>
                        <small v-else>Diambil sendiri</small>
                        <small v-if="r.cancelled_at_ms" style="color: var(--bad-text)"><Icon name="x-circle" :size="14" /> Dibatalkan {{ formatDayTime(r.cancelled_at_ms) }} oleh {{ r.cancelled_by ?? '—' }}</small>
                    </div>
                    <span class="when">
                        <b class="num">{{ formatDayTime(r.collected_at_ms) }}</b><small>WITA</small>
                        <button v-if="canAdmin() && !r.cancelled_at_ms" class="btn sm danger" :disabled="busy === r.id" @click="cancel(r)">{{ busy === r.id ? '…' : 'Batalkan' }}</button>
                    </span>
                </li>
            </ul>
            <p v-else class="empty">{{ data.items.length ? 'Tidak ada yang cocok.' : 'Belum ada race pack yang diserahkan.' }}</p>
        </template>
    </main>
</template>
