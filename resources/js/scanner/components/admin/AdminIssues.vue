<script setup>
import { ref } from 'vue';
import { adminApi } from '../../store.js';
import { usePoll } from '../../admin.js';
import Icon from '../Icon.vue';
import ScanRow from './ScanRow.vue';

/**
 * Scan bermasalah: BIB tak terdaftar / salah kategori (perbaiki BIB / pulihkan), scan dengan
 * jam HP janggal (abaikan), hasil perlu review (ketuk → lacak), dan scan yang diabaikan admin.
 */
const emit = defineEmits(['back', 'track', 'notify']);
const { data, error, loading, load } = usePoll(() => adminApi('/issues'), 30_000);
const tab = ref('unmatched');

const TABS = [
    { key: 'unmatched', label: 'Tak cocok' },
    { key: 'flagged', label: 'Jam janggal' },
    { key: 'review', label: 'Perlu review' },
    { key: 'ignored', label: 'Diabaikan' },
];
const HELP = {
    unmatched: 'BIB tidak terdaftar / salah ketik / kategori tidak lewat pos. Ketuk "BIB" untuk menghubungkan ke peserta yang benar.',
    flagged: 'Jam terkoreksi jauh di masa depan — jam HP marshal kemungkinan salah. Abaikan bila tidak masuk akal.',
    review: 'Hasil yang perlu dicek manual (urutan pos janggal, lewat cut off, dll). Ketuk untuk melihat detail peserta.',
    ignored: 'Scan yang diabaikan admin. Pulihkan bila keliru.',
};
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Scan Bermasalah</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <div class="chips">
            <button v-for="t in TABS" :key="t.key" :class="{ on: tab === t.key }" @click="tab = t.key">
                {{ t.label }} <b v-if="data" class="num">{{ data[t.key].length }}</b>
            </button>
        </div>
        <p class="tip"><Icon name="alert" :size="18" /><span>{{ HELP[tab] }}</span></p>

        <template v-if="data">
            <ul v-if="tab === 'review' && data.review.length" class="alist">
                <li v-for="r in data.review" :key="r.bib" class="tap" @click="emit('track', r.bib)">
                    <b class="bib num">{{ r.bib }}</b>
                    <div class="grow"><b>{{ r.name }}</b><small>{{ r.category }} · {{ r.note }}</small></div>
                    <Icon name="chevron-right" :size="20" />
                </li>
            </ul>
            <ul v-else-if="tab !== 'review' && data[tab].length" class="alist scans">
                <ScanRow v-for="s in data[tab]" :key="s.id" :scan="s" @changed="load" @notify="emit('notify', $event)" @track="emit('track', $event)" />
            </ul>
            <p v-else class="empty">Tidak ada.</p>
        </template>
    </main>
</template>
