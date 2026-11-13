<script setup>
import { computed } from 'vue';
import { state, adminApi } from '../../store.js';
import { usePoll, ago } from '../../admin.js';
import Icon from '../Icon.vue';

/**
 * Checklist kesiapan sebelum START: item dari server (peserta, jadwal, marshal & login HP,
 * data uji, publikasi, racepack) + kondisi HP admin ini (jam, data peserta, antrean).
 */
const emit = defineEmits(['back', 'open']);
const { data, error, loading, load } = usePoll(() => adminApi('/checklist'), 30_000);

const ICON = { ok: 'check-circle', warn: 'alert', info: 'clock' };
const ACTION = { clean: { view: 'reset', label: 'Reset data uji' }, marshals: { view: 'posts', label: 'Status pos' }, logins: { view: 'posts', label: 'Status pos' } };

const device = computed(() => {
    const off = Math.abs(state.clock.offsetMs ?? 0);
    const synced = state.clock.syncedAt;
    return [
        {
            key: 'clock', label: 'Jam HP ini sinkron',
            state: synced && off < 2000 ? 'ok' : 'warn',
            detail: synced ? `Selisih ${off < 1000 ? off + ' ms' : (off / 1000).toFixed(1) + ' dtk'} dari server · dicek ${ago(synced, Date.now())}` : 'Belum sinkron — buka saat ada sinyal',
        },
        {
            key: 'roster', label: 'Data peserta di HP ini',
            state: state.rosterCount ? 'ok' : 'warn',
            detail: state.rosterCount ? `${state.rosterCount} peserta tersimpan` : 'Belum diunduh',
        },
        {
            key: 'queue', label: 'Antrean HP ini terkirim',
            state: state.pending ? 'warn' : 'ok',
            detail: state.pending ? `${state.pending} data belum terkirim` : 'Tidak ada antrean',
        },
    ];
});
const items = computed(() => [...(data.value?.items ?? []), ...device.value]);
const warnCount = computed(() => items.value.filter((i) => i.state === 'warn').length);
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Checklist Kesiapan</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <div v-if="data" class="alert" :class="warnCount ? 'warn' : ''">
            <Icon :name="warnCount ? 'alert' : 'check-circle'" :size="20" />
            <span>{{ warnCount ? `${warnCount} hal perlu dicek sebelum START.` : 'Semua siap. Selamat race!' }}</span>
        </div>

        <ul class="checklist">
            <li v-for="i in items" :key="i.key" :class="i.state">
                <span class="ck"><Icon :name="ICON[i.state]" :size="20" /></span>
                <div class="grow">
                    <b>{{ i.label }}</b>
                    <small>{{ i.detail }}</small>
                    <button v-if="i.state === 'warn' && ACTION[i.key]" class="btn sm" @click="emit('open', ACTION[i.key].view)">
                        {{ ACTION[i.key].label }} <Icon name="chevron-right" :size="16" />
                    </button>
                </div>
            </li>
        </ul>
    </main>
</template>
