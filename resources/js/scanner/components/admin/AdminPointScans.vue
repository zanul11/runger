<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { adminApi } from '../../store.js';
import { formatTime, nowMs } from '../../clock.js';
import { usePoll } from '../../admin.js';
import Icon from '../Icon.vue';
import ScanRow from './ScanRow.vue';

/**
 * Scan per pos: semua scan di satu pos (terbaru dulu) + peserta yang belum lewat, dengan
 * koreksi: abaikan / pulihkan / perbaiki BIB (ScanRow) dan tambah scan manual.
 */
const props = defineProps({ pointId: { type: Number, required: true } });
const emit = defineEmits(['back', 'track', 'notify']);

const q = ref('');
const filter = ref('all');   // all | valid | ignored | missing
const query = computed(() => {
    const p = new URLSearchParams();
    if (q.value.trim()) p.set('q', q.value.trim());
    if (filter.value === 'valid' || filter.value === 'ignored') p.set('filter', filter.value);
    return p.toString();
});
const { data, error, loading, load } = usePoll(() => adminApi(`/points/${props.pointId}/scans?${query.value}`), 20_000);

let debounce;
watch(q, () => {
    clearTimeout(debounce);
    debounce = setTimeout(load, 300);
});
watch(filter, () => load());
onBeforeUnmount(() => clearTimeout(debounce));

const FILTERS = [
    { key: 'all', label: 'Semua' },
    { key: 'valid', label: 'Sah' },
    { key: 'ignored', label: 'Diabaikan' },
    { key: 'missing', label: 'Belum lewat' },
];
const count = (key) => {
    const s = data.value?.stats;
    if (!s) return '';
    return { all: s.valid + s.ignored, valid: s.valid, ignored: s.ignored, missing: s.missing }[key];
};

// ---- tambah scan manual ----
const adding = ref(false);
const addBib = ref('');
const addTime = ref('');
const busy = ref(false);
const bibInput = ref(null);

function openAdd(bib = '') {
    addBib.value = bib;
    addTime.value = formatTime(nowMs());
    adding.value = true;
    nextTick(() => {
        bibInput.value?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        if (!bib) bibInput.value?.focus();
    });
}

async function saveAdd() {
    const bib = addBib.value.trim();
    const time = addTime.value.trim();
    if (!bib || !/^\d{1,2}:\d{2}(:\d{2})?$/.test(time)) {
        emit('notify', 'Isi BIB dan jam (contoh 07:42:10).');
        return;
    }
    if (!confirm(`Tambah scan BIB ${bib} di ${data.value?.point.code} jam ${time} WITA?`)) return;
    busy.value = true;
    try {
        const res = await adminApi(`/points/${props.pointId}/scans`, { method: 'POST', body: { bib, time } });
        emit('notify', res.message);
        adding.value = false;
        await load();
    } catch (e) {
        emit('notify', e.message || 'Gagal menambah scan');
    } finally {
        busy.value = false;
    }
}

const STATUS = { dnf: 'DNF', dq: 'DQ', dns: 'DNS' };
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>{{ data ? `Scan ${data.point.code}` : 'Scan Pos' }}</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <section class="acard">
                <div class="acard-head">
                    <span class="code">{{ data.point.code }}</span>
                    <div class="grow">
                        <b>{{ data.point.name }}</b>
                        <small>{{ data.stats.participants }} dari {{ data.stats.expected }} peserta sudah lewat</small>
                    </div>
                    <b class="big num" :class="{ hot: data.stats.missing }">{{ data.stats.missing }}</b>
                </div>
                <button v-if="!adding" class="btn block" @click="openAdd()"><Icon name="plus-square" :size="20" /> Tambah scan manual</button>
                <div v-else class="addscan">
                    <div class="field">
                        <span>BIB</span>
                        <div class="input"><input ref="bibInput" v-model="addBib" inputmode="numeric" placeholder="7001" autocomplete="off"></div>
                    </div>
                    <div class="field">
                        <span>Jam lewat (WITA)</span>
                        <div class="input"><input v-model="addTime" inputmode="decimal" placeholder="07:42:10" autocomplete="off"></div>
                    </div>
                    <button class="btn" :disabled="busy" @click="adding = false">Batal</button>
                    <button class="btn primary" :disabled="busy" @click="saveAdd">{{ busy ? 'Menyimpan…' : 'Simpan' }}</button>
                    <p class="note">Untuk peserta yang terbukti lewat tapi tidak ter-scan. Tercatat sebagai "Koreksi admin".</p>
                </div>
            </section>

            <div class="input search sticky">
                <Icon name="search" :size="20" />
                <input v-model="q" type="search" inputmode="search" placeholder="Cari BIB atau nama" autocomplete="off">
                <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
            </div>
            <div class="chips">
                <button v-for="f in FILTERS" :key="f.key" :class="{ on: filter === f.key }" @click="filter = f.key">
                    {{ f.label }} <b class="num">{{ count(f.key) }}</b>
                </button>
            </div>

            <template v-if="filter === 'missing'">
                <ul v-if="data.missing.length" class="alist">
                    <li v-for="p in data.missing" :key="p.bib">
                        <b class="bib num">{{ p.bib }}</b>
                        <button class="grow who-btn" @click="emit('track', p.bib)">
                            <b>{{ p.name }}</b>
                            <small>{{ p.category }}<template v-if="STATUS[p.race_status]"> · {{ STATUS[p.race_status] }}</template></small>
                        </button>
                        <button class="btn sm" @click="openAdd(p.bib)"><Icon name="plus-square" :size="16" /> Scan</button>
                    </li>
                </ul>
                <p v-else class="empty">Semua peserta sudah lewat pos ini.</p>
            </template>
            <template v-else>
                <ul v-if="data.scans.length" class="alist scans">
                    <ScanRow v-for="s in data.scans" :key="s.id" :scan="s" @changed="load" @notify="emit('notify', $event)" @track="emit('track', $event)" />
                </ul>
                <p v-else class="empty">{{ q ? 'Tidak ada scan yang cocok.' : 'Belum ada scan di pos ini.' }}</p>
                <p v-if="data.scans.length >= 300" class="note">Menampilkan 300 scan terbaru — pakai pencarian untuk yang lebih lama.</p>
            </template>
        </template>
    </main>
</template>
