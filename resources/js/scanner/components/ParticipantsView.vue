<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { state, refreshRoster, participantOverview, pointCategoryIds, categoryName, visitsFor } from '../store.js';
import { formatTime } from '../clock.js';
import Icon from './Icon.vue';

/**
 * Data peserta yang tersimpan di HP: memastikan roster sudah terunduh (jumlah, waktu unduh,
 * per kategori), unduh ulang, dan daftar yang bisa dicari beserta status di pos / racepack.
 */
const props = defineProps({ mode: { type: String, default: 'timing' } });
const emit = defineEmits(['back', 'notify']);

const list = ref([]);
const q = ref('');
const cat = ref('all');
const filter = ref('all');   // all | done | todo
const busy = ref(false);
const now = ref(Date.now());
let tick;

const GENDER = { M: 'L', F: 'P' };
const isTiming = computed(() => props.mode === 'timing' && !!state.session.timing_point);
const pointCats = computed(() => pointCategoryIds());

async function load() {
    list.value = await participantOverview();
}

onMounted(() => {
    load();
    tick = setInterval(() => { now.value = Date.now(); }, 30_000);
});
onBeforeUnmount(() => clearInterval(tick));

/** Selesai = sudah lewat pos ini (timing) / racepack sudah diambil (racepack). */
function visitCount(p) {
    return Math.max(1, visitsFor(p.category_id).length);
}
function isDone(p) {
    // Kategori yang lewat pos ini 2× (15K di Pertigaan) baru "selesai" setelah lewat ke-2.
    return isTiming.value ? p.passes_ms.length >= visitCount(p) : !!p.racepack;
}
function relevant(p) {
    return !isTiming.value || pointCats.value.includes(p.category_id);
}

const categories = computed(() => {
    const counts = new Map();
    for (const p of list.value) counts.set(p.category_id, (counts.get(p.category_id) ?? 0) + 1);
    return [...counts.entries()].map(([id, n]) => ({ id, name: categoryName(id), n, passes: pointCats.value.includes(id) }));
});

const summary = computed(() => {
    const rel = list.value.filter(relevant);
    return { total: rel.length, done: rel.filter(isDone).length };
});

const shown = computed(() => {
    const term = q.value.trim().toLowerCase();
    return list.value.filter((p) => (cat.value === 'all' || p.category_id === cat.value)
        && (filter.value === 'all' || (filter.value === 'done' ? isDone(p) : !isDone(p) && relevant(p)))
        && (!term || p.bib.includes(term.replace(/^0+/, '')) || p.name.toLowerCase().includes(term)
            || String(p.registration_number ?? '').toLowerCase().includes(term)));
});

const downloaded = computed(() => {
    if (!state.rosterAt) return null;
    const min = Math.round((now.value - state.rosterAt) / 60_000);
    return { time: formatTime(state.rosterAt), ago: min < 1 ? 'baru saja' : min < 60 ? `${min} menit lalu` : `${Math.floor(min / 60)} jam lalu` };
});

async function reload() {
    busy.value = true;
    try {
        await refreshRoster();
        await load();
        emit('notify', `Data peserta diperbarui (${state.rosterCount})`);
    } catch (e) {
        emit('notify', navigator.onLine ? (e.message || 'Gagal mengunduh') : 'Offline — tidak bisa mengunduh sekarang');
    } finally {
        busy.value = false;
    }
}

const doneLabel = computed(() => (isTiming.value ? 'Sudah lewat' : 'Sudah ambil'));
const todoLabel = computed(() => (isTiming.value ? 'Belum lewat' : 'Belum ambil'));
</script>

<template>
    <main class="panel">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Data Peserta</h2>
        </div>

        <!-- Status unduhan -->
        <section class="dl" :class="state.rosterCount ? 'ok' : 'none'">
            <div class="dl-head">
                <Icon :name="state.rosterCount ? 'check-circle' : 'alert'" :size="28" :stroke="2.5" />
                <div>
                    <b>{{ state.rosterCount ? `${state.rosterCount} peserta tersimpan di HP` : 'Belum ada data peserta di HP' }}</b>
                    <small v-if="downloaded">Diunduh {{ downloaded.time }} WITA · {{ downloaded.ago }}</small>
                    <small v-else>Unduh dulu selagi ada sinyal — dibutuhkan untuk scan offline.</small>
                </div>
            </div>
            <div v-if="categories.length" class="dl-cats">
                <span v-for="c in categories" :key="c.id" class="tag">
                    {{ c.name }} <b class="num">{{ c.n }}</b>
                    <em v-if="isTiming && !c.passes">tidak lewat pos</em>
                </span>
            </div>
            <div v-if="state.rosterCount" class="dl-progress">
                <span>{{ doneLabel }}</span>
                <b class="num">{{ summary.done }} / {{ summary.total }}</b>
                <div class="bar"><div :style="{ width: (summary.total ? summary.done / summary.total * 100 : 0) + '%' }"></div></div>
            </div>
            <button class="btn block" :class="state.rosterCount ? '' : 'primary'" :disabled="busy || !state.online" @click="reload">
                <Icon name="refresh" :size="20" /> {{ busy ? 'Mengunduh…' : (state.online ? 'Unduh ulang data peserta' : 'Offline — tidak bisa mengunduh') }}
            </button>
        </section>

        <!-- Cari & filter -->
        <div class="input search">
            <Icon name="list" :size="20" />
            <input v-model="q" type="search" inputmode="search" :placeholder="isTiming ? 'Cari nama atau BIB' : 'Cari nama atau no. registrasi'" autocomplete="off">
            <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
        </div>
        <div class="chips">
            <button :class="{ on: cat === 'all' }" @click="cat = 'all'">Semua</button>
            <button v-for="c in categories" :key="c.id" :class="{ on: cat === c.id }" @click="cat = c.id">{{ c.name }}</button>
            <span class="sep"></span>
            <button :class="{ on: filter === 'todo' }" @click="filter = filter === 'todo' ? 'all' : 'todo'">{{ todoLabel }}</button>
            <button :class="{ on: filter === 'done' }" @click="filter = filter === 'done' ? 'all' : 'done'">{{ doneLabel }}</button>
        </div>

        <div class="section-title"><span>{{ shown.length }} peserta</span></div>
        <ul v-if="shown.length" class="plist">
            <li v-for="p in shown" :key="p.bib" :class="{ dim: !relevant(p) }">
                <b class="num">{{ p.bib }}</b>
                <span class="who">
                    <span>{{ p.name }}</span>
                    <small>{{ categoryName(p.category_id) }} · {{ GENDER[p.gender] ?? '-' }}<template v-if="!isTiming && p.size"> · Jersey {{ p.size }}</template></small>
                </span>
                <span v-if="isTiming && !relevant(p)" class="st muted">tidak lewat</span>
                <span v-else-if="isTiming && isDone(p)" class="st yes">
                    <Icon name="check" :size="16" :stroke="3" /> {{ visitCount(p) > 1 ? `${visitCount(p)}× · ` : '' }}{{ formatTime(p.passes_ms[visitCount(p) - 1]) }}
                </span>
                <span v-else-if="isTiming && p.passes_ms.length" class="st part">{{ p.passes_ms.length }}/{{ visitCount(p) }} · {{ formatTime(p.passes_ms[0]) }}</span>
                <span v-else-if="!isTiming && p.racepack" class="st yes"><Icon name="check" :size="16" :stroke="3" /> Diambil</span>
                <span v-else class="st todo">{{ isTiming ? 'Belum' : 'Belum ambil' }}</span>
            </li>
        </ul>
        <p v-else class="empty">{{ state.rosterCount ? 'Tidak ada peserta yang cocok.' : 'Data peserta belum diunduh.' }}</p>
    </main>
</template>
