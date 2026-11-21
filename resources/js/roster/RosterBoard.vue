<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
    consumption, filledCount, isFilled, kmLabel, matches, needOf, posStatus, positionsOf, range, rosterCsv, shortfall, totals, whatsappText,
} from './logic.js';
import RosterSimple from './RosterSimple.vue';
import './roster.css';

/**
 * Roster panitia hari H — satu komponen untuk GTR Crew (/crew → Roster, bisa mengisi petugas)
 * dan halaman publik /crew/roster (baca-saja, proyektor). Sumber data & penyimpanan lewat props
 * supaya komponen ini tidak tahu soal token / URL.
 */
const props = defineProps({
    load: { type: Function, required: true },                 // () => payload
    saveSlot: { type: Function, default: null },              // (posId, slotNo, fields) => slot
    saveTamu: { type: Function, default: null },              // (n) => void
    exportJson: { type: Function, default: null },            // () => object
    importJson: { type: Function, default: null },            // (mode, data, preview) => result
    updateItem: { type: Function, default: null },            // (type, id, body) => void — admin
    showBack: { type: Boolean, default: false },
    shareUrl: { type: String, default: null },
    pollMs: { type: Number, default: 7000 },
});
const emit = defineEmits(['back']);

const data = ref(null);
const error = ref(null);
const loading = ref(false);
const canFill = computed(() => !!(data.value?.perms?.fill && props.saveSlot));
const isAdmin = computed(() => !!data.value?.perms?.structure);
const ev = computed(() => data.value?.event ?? {});

// ---------------------------------------------------------------- tema & font
const THEME_KEY = 'gtr-roster-theme';
const dark = ref(false);
try {
    const saved = localStorage.getItem(THEME_KEY);
    dark.value = saved ? saved === 'dark' : window.matchMedia?.('(prefers-color-scheme: dark)').matches;
} catch { /* tanpa storage */ }
function toggleDark() {
    dark.value = !dark.value;
    try { localStorage.setItem(THEME_KEY, dark.value ? 'dark' : 'light'); } catch { /* abaikan */ }
}
function loadFonts() {
    if (document.getElementById('rg-fonts')) return;
    const l = document.createElement('link');
    l.id = 'rg-fonts';
    l.rel = 'stylesheet';
    l.href = 'https://fonts.bunny.net/css?family=barlow-condensed:600,700,700i,800i|barlow:400,500,600,700&display=swap';
    document.head.appendChild(l);
}

// ---------------------------------------------------------------- toast
const toast = ref(null);
let toastTimer;
function notify(msg) {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 3200);
}

// ---------------------------------------------------------------- muat & polling
// Slot yang sedang diketik / menunggu simpan dikunci: nilai lokalnya tidak ditimpa polling.
const locks = new Set();
const focused = new Set();
const pending = {};          // key → field yang belum terkirim
const timers = {};
const inflight = new Set();
const slotState = reactive({}); // key → saving | saved | error
const saving = ref(0);
const lastSavedAt = ref(null);
const failed = computed(() => Object.values(slotState).filter((s) => s === 'error').length);
let lastRaw = '';

const slotKey = (pid, no) => `${pid}:${no}`;
function slotIndex(d) {
    const m = new Map();
    for (const a of d?.areas ?? []) for (const g of a.groups) for (const p of g.positions) for (const s of p.slots) m.set(slotKey(p.id, s.slot_no), s);
    return m;
}
const findSlot = (key) => slotIndex(data.value).get(key);

async function load() {
    if (loading.value) return;
    loading.value = true;
    try {
        const next = await props.load();
        const raw = JSON.stringify(next);
        if (raw !== lastRaw) {
            lastRaw = raw;
            if (data.value) {
                const old = slotIndex(data.value);
                for (const [key, s] of slotIndex(next)) {
                    if (locks.has(key) && old.has(key)) {
                        const o = old.get(key);
                        Object.assign(s, { nama: o.nama, no_hp: o.no_hp, catatan: o.catatan });
                    }
                }
            }
            data.value = next;
            if (!tamuLocked) tamu.value = next.consumption?.tamu_tambahan ?? 0;
        }
        error.value = null;
    } catch (e) {
        error.value = navigator.onLine ? (e.message || 'Gagal memuat roster') : 'Offline — menampilkan data terakhir';
    } finally {
        loading.value = false;
    }
}

let pollTimer;
function loop() {
    clearTimeout(pollTimer);
    pollTimer = setTimeout(async () => {
        if (document.visibilityState === 'visible' && navigator.onLine) await load();
        loop();
    }, props.pollMs);
}
const onVisible = () => { if (document.visibilityState === 'visible') load(); };
onMounted(() => {
    loadFonts();
    load();
    loop();
    document.addEventListener('visibilitychange', onVisible);
    window.addEventListener('beforeunload', warnUnsaved);
});
onBeforeUnmount(() => {
    clearTimeout(pollTimer);
    document.removeEventListener('visibilitychange', onVisible);
    window.removeEventListener('beforeunload', warnUnsaved);
    Object.keys(pending).forEach((k) => flush(k));
});
function warnUnsaved(e) {
    if (Object.keys(pending).length || inflight.size) {
        e.preventDefault();
        e.returnValue = '';
    }
}

// ---------------------------------------------------------------- simpan otomatis per field
function onFocus(key) {
    focused.add(key);
    locks.add(key);
}
function onBlur(key) {
    focused.delete(key);
    if (pending[key]) {
        clearTimeout(timers[key]);
        flush(key);
    }
    unlockIfIdle(key);
}
function unlockIfIdle(key) {
    if (!focused.has(key) && !pending[key] && !inflight.has(key)) locks.delete(key);
}
function onInput(pid, s, field) {
    const key = slotKey(pid, s.slot_no);
    locks.add(key);
    pending[key] = { ...pending[key], [field]: s[field] };
    if (slotState[key] !== 'error') slotState[key] = 'dirty';
    clearTimeout(timers[key]);
    timers[key] = setTimeout(() => flush(key), 500);
}
async function flush(key) {
    const fields = pending[key];
    if (!fields || inflight.has(key)) return;
    delete pending[key];
    inflight.add(key);
    saving.value++;
    slotState[key] = 'saving';
    const [pid, no] = key.split(':').map(Number);
    try {
        const res = await props.saveSlot(pid, no, fields);
        const s = findSlot(key);
        if (s && res) Object.assign(s, { updated_by: res.updated_by, updated_at_ms: res.updated_at_ms });
        slotState[key] = pending[key] ? 'dirty' : 'saved';
        lastSavedAt.value = Date.now();
    } catch (e) {
        pending[key] = { ...fields, ...pending[key] };
        slotState[key] = 'error';
        notify(navigator.onLine ? (e.message || 'Gagal menyimpan') : 'Offline — isian belum tersimpan, coba lagi saat ada sinyal');
    } finally {
        inflight.delete(key);
        saving.value--;
        if (pending[key] && slotState[key] !== 'error') flush(key);
        unlockIfIdle(key);
    }
}
function retryFailed() {
    Object.keys(slotState).filter((k) => slotState[k] === 'error').forEach((k) => flush(k));
}
const saveLabel = computed(() => {
    if (saving.value > 0) return { cls: 'busy', text: 'Menyimpan…' };
    if (failed.value) return { cls: 'bad', text: `${failed.value} gagal disimpan` };
    if (lastSavedAt.value) return { cls: 'ok', text: `Tersimpan ${new Date(lastSavedAt.value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}` };
    return null;
});

function slotTitle(s) {
    if (!s.updated_by && !s.updated_at_ms) return undefined;
    const when = s.updated_at_ms ? new Date(s.updated_at_ms).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
    return `Terakhir diubah${s.updated_by ? ` oleh ${s.updated_by}` : ''}${when ? ` · ${when}` : ''}`;
}

// ---------------------------------------------------------------- tamu konsumsi
const tamu = ref(0);
let tamuLocked = false;
let tamuTimer;
function onTamu() {
    tamuLocked = true;
    clearTimeout(tamuTimer);
    tamuTimer = setTimeout(async () => {
        const n = Math.max(0, parseInt(tamu.value, 10) || 0);
        try {
            saving.value++;
            await props.saveTamu?.(n);
            if (data.value) data.value.consumption.tamu_tambahan = n;
            lastSavedAt.value = Date.now();
        } catch (e) {
            notify(e.message || 'Gagal menyimpan jumlah tamu');
        } finally {
            saving.value--;
            tamuLocked = false;
        }
    }, 600);
}

// ---------------------------------------------------------------- filter
const tab = ref('all');
const q = ref('');
const onlyShort = ref(false);
const allPositions = computed(() => (data.value?.areas ?? []).flatMap(positionsOf));
const total = computed(() => totals(allPositions.value));
const areaTotals = computed(() => (data.value?.areas ?? []).map((a) => ({ area: a, t: totals(positionsOf(a)) })));
const filtering = computed(() => !!q.value.trim() || onlyShort.value);
const view = computed(() => (data.value?.areas ?? [])
    .filter((a) => tab.value === 'all' || a.id === tab.value)
    .map((a) => ({
        ...a,
        groups: a.groups
            .map((g) => ({ ...g, positions: g.positions.filter((p) => (!onlyShort.value || filledCount(p) < p.min) && matches(p, g, a, q.value)) }))
            .filter((g) => g.positions.length),
    }))
    .filter((a) => a.groups.length));
const shownCount = computed(() => view.value.reduce((n, a) => n + a.groups.reduce((m, g) => m + g.positions.length, 0), 0));
watch(() => data.value?.areas?.map((a) => a.id).join(), () => {
    if (tab.value !== 'all' && !data.value?.areas?.some((a) => a.id === tab.value)) tab.value = 'all';
});

const pct = (a, b) => (b > 0 ? Math.min(100, Math.round((a / b) * 100)) : 0);
const icon = (key) => data.value?.icons?.[key] ?? data.value?.icons?.flag ?? [];
const cons = computed(() => consumption(data.value, tamu.value));
const STATUS_LABEL = { ok: 'Terpenuhi', part: 'Kurang', empty: 'Kosong' };

// ---------------------------------------------------------------- ringkasan WA / export / cetak
const copyBox = ref(null);
async function copyWa() {
    const text = whatsappText(data.value, tamu.value);
    try {
        await navigator.clipboard.writeText(text);
        notify('Ringkasan disalin — tempel di grup WA panitia.');
    } catch {
        copyBox.value = text;
    }
}
function download(name, content, type) {
    const url = URL.createObjectURL(new Blob([content], { type }));
    const a = Object.assign(document.createElement('a'), { href: url, download: name });
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
const fileBase = computed(() => `roster-${(ev.value.nama ?? 'event').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`);
const stamp = () => new Date().toISOString().slice(0, 16).replace(/[-:T]/g, '');
async function doExport() {
    try {
        const json = await props.exportJson();
        download(`${fileBase.value}-${stamp()}.json`, JSON.stringify(json, null, 2), 'application/json');
    } catch (e) {
        notify(e.message || 'Gagal export');
    }
}
const printPage = () => window.print();
function doCsv() {
    download(`${fileBase.value}-${stamp()}.csv`, rosterCsv(data.value), 'text/csv;charset=utf-8');
}
async function share() {
    try {
        if (navigator.share) {
            await navigator.share({ title: `Roster Panitia — ${ev.value.nama}`, url: props.shareUrl });
            return;
        }
        await navigator.clipboard.writeText(props.shareUrl);
        notify('Link roster (baca-saja) disalin.');
    } catch (e) {
        if (e?.name !== 'AbortError') notify(props.shareUrl);
    }
}

// ---------------------------------------------------------------- import
const imp = ref(null); // { mode, name, data, preview, busy, error }
function openImport() {
    imp.value = { mode: 'petugas', name: null, data: null, preview: null, busy: false, error: null };
}
async function pickFile(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    imp.value.name = file.name;
    imp.value.preview = null;
    imp.value.error = null;
    try {
        imp.value.data = JSON.parse(await file.text());
        await runPreview();
    } catch (err) {
        imp.value.data = null;
        imp.value.error = err instanceof SyntaxError ? 'File bukan JSON yang valid.' : (err.message || 'Gagal membaca file');
    }
}
async function runPreview() {
    if (!imp.value?.data) return;
    imp.value.busy = true;
    imp.value.error = null;
    try {
        imp.value.preview = await props.importJson(imp.value.mode, imp.value.data, true);
    } catch (e) {
        imp.value.preview = null;
        imp.value.error = e.body?.errors ? Object.values(e.body.errors).flat().join(' ') : (e.message || 'Preview gagal');
    } finally {
        imp.value.busy = false;
    }
}
async function applyImport() {
    if (imp.value.mode === 'struktur' && !confirm('Struktur roster akan disamakan dengan file: pos yang tidak ada di file DIHAPUS beserta petugasnya. Lanjutkan?')) return;
    imp.value.busy = true;
    try {
        const res = await props.importJson(imp.value.mode, imp.value.data, false);
        notify(res.message);
        imp.value = null;
        lastRaw = '';
        await load();
    } catch (e) {
        imp.value.busy = false;
        imp.value.error = e.message || 'Import gagal';
    }
}
// ---------------------------------------------------------------- ubah dari Crew app (admin)
// Area & kelompok: ganti nama. Pos: nama, jumlah orang (min–maks), jobdesk.
const canRename = computed(() => isAdmin.value && !!props.updateItem);
const ren = ref(null); // { type, id, label, nama, min?, max?, deskripsi?, busy, error, canCompact }
const REN_LABEL = { areas: 'area', groups: 'kelompok', positions: 'pos' };
function openRename(type, item) {
    ren.value = {
        type, id: item.id, label: REN_LABEL[type], nama: item.nama, busy: false, error: null, canCompact: false,
        ...(type === 'positions' ? { min: item.min, max: item.max, deskripsi: item.deskripsi ?? '', filled: filledCount(item), origMax: item.max } : {}),
    };
}
function step(field, d) {
    const r = ren.value;
    r[field] = Math.max(field === 'max' ? 1 : 0, Math.min(50, (parseInt(r[field], 10) || 0) + d));
    // Jaga min ≤ maks: menaikkan min ikut menaikkan maks, menurunkan maks ikut menurunkan min.
    if (field === 'min' && r.min > r.max) r.max = r.min;
    if (field === 'max' && r.max < r.min) r.min = r.max;
    r.error = null;
    r.canCompact = false;
}
async function saveRename(compact = false) {
    const r = ren.value;
    const nama = r.nama.trim();
    if (!nama) return notify('Nama wajib diisi.');
    const body = r.type === 'positions'
        ? { nama, min_petugas: parseInt(r.min, 10) || 0, max_petugas: parseInt(r.max, 10) || 1, deskripsi: r.deskripsi.trim() || null, ...(compact ? { compact: true } : {}) }
        : { nama };
    r.busy = true;
    r.error = null;
    try {
        await props.updateItem(r.type, r.id, body);
        notify(r.type === 'positions' ? 'Pos diperbarui.' : `Nama ${r.label} diganti.`);
        ren.value = null;
        lastRaw = '';
        await load();
    } catch (e) {
        r.busy = false;
        r.error = e.body?.message || e.message || 'Gagal menyimpan';
        r.canCompact = !!e.body?.can_compact;
    }
}

const CHANGE_LABEL = { tambah: 'Tambah', ubah: 'Ubah', kosongkan: 'Kosongkan', hapus: 'Hapus' };

// ---------------------------------------------------------------- ornamen kontur topografi
function contour(cx, cy, r, seed) {
    const n = 14;
    const pts = [];
    for (let i = 0; i < n; i++) {
        const a = (i / n) * Math.PI * 2;
        const k = 1 + 0.16 * Math.sin(3 * a + seed) + 0.07 * Math.cos(5 * a + seed * 1.7);
        pts.push([cx + Math.cos(a) * r * 1.5 * k, cy + Math.sin(a) * r * 0.82 * k]);
    }
    let d = `M${pts[0][0].toFixed(1)},${pts[0][1].toFixed(1)}`;
    for (let i = 0; i < n; i++) {
        const p0 = pts[(i - 1 + n) % n], p1 = pts[i], p2 = pts[(i + 1) % n], p3 = pts[(i + 2) % n];
        const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6];
        const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6];
        d += ` C${c1[0].toFixed(1)},${c1[1].toFixed(1)} ${c2[0].toFixed(1)},${c2[1].toFixed(1)} ${p2[0].toFixed(1)},${p2[1].toFixed(1)}`;
    }
    return d + 'Z';
}
const contours = [
    ...Array.from({ length: 11 }, (_, i) => contour(1020, 40, 26 + i * 30, i * 0.45)),
    ...Array.from({ length: 7 }, (_, i) => contour(140, 330, 22 + i * 32, 2 + i * 0.5)),
];
</script>

<template>
    <div class="rg" :class="{ dark }">
        <RosterSimple v-if="data && !canFill" :data="data" :tamu="tamu" :show-back="props.showBack" :error="error" @back="emit('back')" />
        <template v-else>
        <header class="rg-hero">
            <svg class="rg-topo" viewBox="0 0 1200 360" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <path v-for="(d, i) in contours" :key="i" :d="d" />
            </svg>
            <div class="rg-wrap rg-hero-in">
                <div class="rg-topline">
                    <button v-if="props.showBack" class="rg-back" @click="emit('back')">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path d="M15 18l-6-6 6-6" /></svg> GTR Crew
                    </button>
                    <span class="rg-kicker">Roster Panitia Hari H</span>
                    <span v-if="data && !canFill" class="rg-ro">Lihat saja</span>
                </div>
                <template v-if="data">
                    <h1>{{ ev.nama }}</h1>
                    <ul class="rg-facts">
                        <li v-if="ev.tanggal_label"><b>Tanggal</b>{{ ev.tanggal_label }}</li>
                        <li v-if="ev.lokasi_race_village"><b>Race Village</b>{{ ev.lokasi_race_village }}</li>
                        <li v-if="ev.lokasi_rute"><b>Rute</b>{{ ev.lokasi_rute }}</li>
                        <li v-if="ev.kategori"><b>Kategori</b>{{ ev.kategori }}</li>
                        <li v-if="ev.catatan_open_gate"><b>Open gate</b>{{ ev.catatan_open_gate }}</li>
                    </ul>

                    <div class="rg-sums">
                        <div v-for="c in [{ key: 'all', label: 'Total', t: total }, ...areaTotals.map((x) => ({ key: x.area.id, label: x.area.nama, t: x.t }))]"
                             :key="c.key" class="rg-sum" :class="{ ok: !c.t.short, main: c.key === 'all' }">
                            <small>{{ c.label }}</small>
                            <p class="rg-sum-n"><b class="num">{{ c.t.filled }}</b> terisi dari <span class="num">{{ range(c.t.min, c.t.max) }}</span> orang</p>
                            <div class="rg-bar" role="img" :aria-label="`${c.t.filled} dari maksimal ${c.t.max}, minimal ${c.t.min}`">
                                <i :style="{ width: pct(c.t.filled, c.t.max) + '%' }"></i>
                                <em :style="{ left: pct(c.t.min, c.t.max) + '%' }" title="Batas minimal"></em>
                            </div>
                            <p class="rg-sum-s">{{ c.t.short ? `Kurang ${c.t.short} orang dari minimal` : 'Minimal terpenuhi ✓' }}</p>
                        </div>
                    </div>
                </template>
                <p v-else-if="error" class="rg-herr">{{ error }}</p>
                <p v-else class="rg-herr">Memuat roster…</p>
            </div>
        </header>

        <template v-if="data">
            <div class="rg-toolbar">
                <div class="rg-wrap">
                    <div class="rg-tabs" role="tablist">
                        <button role="tab" :aria-selected="tab === 'all'" :class="{ on: tab === 'all' }" @click="tab = 'all'">
                            Semua <span class="rg-badge num">{{ total.filled }}/{{ total.min }}</span>
                        </button>
                        <button v-for="x in areaTotals" :key="x.area.id" role="tab" :aria-selected="tab === x.area.id" :class="{ on: tab === x.area.id, short: x.t.short }" @click="tab = x.area.id">
                            {{ x.area.nama }} <span class="rg-badge num">{{ x.t.filled }}/{{ x.t.min }}</span>
                        </button>
                    </div>
                    <div class="rg-tools">
                        <label class="rg-search">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35" /></svg>
                            <input v-model="q" type="search" placeholder="Cari pos, grup, KM (8,1) atau nama petugas" autocomplete="off" aria-label="Cari">
                        </label>
                        <label class="rg-check"><input v-model="onlyShort" type="checkbox"> Hanya yang belum penuh</label>
                        <div class="rg-actions">
                            <button class="rg-btn primary" @click="copyWa">Salin ringkasan (WA)</button>
                            <button v-if="props.exportJson" class="rg-btn" @click="doExport">Export JSON</button>
                            <button v-if="canFill && props.importJson" class="rg-btn" @click="openImport">Import JSON</button>
                            <button class="rg-btn" @click="doCsv">Excel (CSV)</button>
                            <button class="rg-btn" @click="printPage">Cetak / PDF</button>
                            <button v-if="props.shareUrl" class="rg-btn" @click="share">Link baca-saja</button>
                            <button class="rg-btn icon" :aria-label="dark ? 'Mode terang' : 'Mode gelap'" :title="dark ? 'Mode terang' : 'Mode gelap'" @click="toggleDark">
                                <svg v-if="dark" viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></svg>
                                <svg v-else viewBox="0 0 24 24" width="18" height="18"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" /></svg>
                            </button>
                        </div>
                        <span v-if="saveLabel" class="rg-saved" :class="saveLabel.cls" role="status">
                            {{ saveLabel.text }}<button v-if="saveLabel.cls === 'bad'" class="rg-link" @click="retryFailed">Coba lagi</button>
                        </span>
                    </div>
                    <p v-if="error" class="rg-alert">{{ error }}</p>
                </div>
            </div>

            <main class="rg-wrap rg-main">
                <p v-if="!data.areas.length" class="rg-empty">
                    Struktur roster belum dibuat. Admin mengatur area, grup &amp; pos di panel admin → <b>Struktur Roster</b>.
                </p>
                <p v-else-if="filtering && !shownCount" class="rg-empty">Tidak ada pos yang cocok.</p>

                <section v-for="a in view" :key="a.id" class="rg-area">
                    <h2><span>{{ a.nama }}</span><button v-if="canRename" class="rg-edit" :aria-label="`Ganti nama ${a.nama}`" title="Ganti nama" @click="openRename('areas', a)"><svg viewBox="0 0 24 24" width="15" height="15"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z" /></svg></button></h2>
                    <div v-for="g in a.groups" :key="g.id" class="rg-group">
                        <h3>{{ g.nama }}<button v-if="canRename" class="rg-edit" :aria-label="`Ganti nama ${g.nama}`" title="Ganti nama" @click="openRename('groups', g)"><svg viewBox="0 0 24 24" width="15" height="15"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z" /></svg></button></h3>
                        <div class="rg-grid">
                            <article v-for="p in g.positions" :key="p.id" class="rg-pos" :class="'st-' + posStatus(p)">
                                <header class="rg-pos-head">
                                    <span class="rg-ic">
                                        <svg viewBox="0 0 24 24" width="22" height="22"><path v-for="(d, i) in icon(p.ikon)" :key="i" :d="d" /></svg>
                                    </span>
                                    <div class="rg-pos-title">
                                        <h4>{{ p.nama }}<button v-if="canRename" class="rg-edit" :aria-label="`Ubah pos ${p.nama}`" title="Ubah nama, jumlah orang & jobdesk" @click="openRename('positions', p)"><svg viewBox="0 0 24 24" width="15" height="15"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z" /></svg></button></h4>
                                        <div v-if="p.km.length" class="rg-kms"><span v-for="k in p.km" :key="k" class="rg-km">KM {{ k }}</span></div>
                                    </div>
                                    <span class="rg-count num" :title="STATUS_LABEL[posStatus(p)]">{{ filledCount(p) }}/{{ needOf(p) }}</span>
                                </header>
                                <p v-if="p.deskripsi" class="rg-desc">{{ p.deskripsi }}</p>
                                <p class="rg-need">Kebutuhan: <b>{{ range(p.min, p.max) }} orang</b><span v-if="p.catatan_tambahan" class="rg-extra">{{ p.catatan_tambahan }}</span></p>
                                <ol class="rg-slots">
                                    <li v-for="(s, i) in p.slots" :key="s.slot_no" :class="{ opt: i >= p.min, filled: isFilled(s), [slotState[`${p.id}:${s.slot_no}`] ?? '']: true }" :title="slotTitle(s)">
                                        <span class="rg-no num">{{ s.slot_no }}</span>
                                        <template v-if="canFill">
                                            <input v-model="s.nama" class="rg-in name" maxlength="120" placeholder="Nama" autocomplete="off" :aria-label="`${p.nama} slot ${s.slot_no} — nama`"
                                                   @focus="onFocus(`${p.id}:${s.slot_no}`)" @blur="onBlur(`${p.id}:${s.slot_no}`)" @input="onInput(p.id, s, 'nama')">
                                            <input v-model="s.no_hp" class="rg-in hp num" maxlength="32" placeholder="No. HP" inputmode="tel" autocomplete="off" :aria-label="`${p.nama} slot ${s.slot_no} — no. HP`"
                                                   @focus="onFocus(`${p.id}:${s.slot_no}`)" @blur="onBlur(`${p.id}:${s.slot_no}`)" @input="onInput(p.id, s, 'no_hp')">
                                        </template>
                                        <span class="rg-val" :class="{ ro: canFill }">
                                            <b>{{ s.nama || '—' }}</b><span v-if="s.no_hp" class="num">{{ s.no_hp }}</span>
                                        </span>
                                        <em v-if="i >= p.min" class="rg-opt">opsional</em>
                                        <i class="rg-dot" aria-hidden="true"></i>
                                    </li>
                                </ol>
                                <p v-if="shortfall(p)" class="rg-warn">⚠️ Kurang {{ shortfall(p) }} orang</p>
                            </article>
                        </div>
                    </div>
                </section>

                <section v-if="data.areas.length && tab === 'all' && !filtering" class="rg-cons">
                    <h2><span>Konsumsi Race Village</span></h2>
                    <div class="rg-cons-in">
                        <label class="rg-tamu">
                            <span>Tamu / VIP / sponsor / tambahan</span>
                            <input v-if="canFill && props.saveTamu" v-model.number="tamu" type="number" min="0" max="5000" inputmode="numeric" class="rg-in num" @input="onTamu">
                            <b v-else class="num">{{ tamu }}</b>
                        </label>
                        <p class="rg-basis">
                            Dasar hitungan: <b class="num">{{ cons.dasar }}</b> orang
                            = <span class="num">{{ cons.panitia }}</span> panitia ({{ cons.basisLabel }}) + <span class="num">{{ cons.tamu }}</span> tamu
                        </p>
                        <div class="rg-cons-grid">
                            <div><b class="num">{{ cons.sarapan }}</b><small>Porsi sarapan</small></div>
                            <div><b class="num">{{ cons.makanSiang }}</b><small>Makan siang</small></div>
                            <div><b class="num">{{ cons.snack }}</b><small>Snack box</small></div>
                            <div><b class="num">{{ cons.air }}</b><small>Air mineral ({{ cons.airPerOrang }}/orang)</small></div>
                            <div v-if="cons.lapanganNama" class="alt"><b class="num">{{ cons.lapangan }}</b><small>Paket sarapan ke pos {{ cons.lapanganNama.toLowerCase() }}</small></div>
                        </div>
                        <table v-if="data.consumption.jadwal.length" class="rg-table">
                            <thead><tr><th>Waktu</th><th>Kegiatan</th></tr></thead>
                            <tbody><tr v-for="(j, i) in data.consumption.jadwal" :key="i"><td class="num">{{ j.waktu }}</td><td>{{ j.kegiatan }}</td></tr></tbody>
                        </table>
                        <p v-if="data.consumption.catatan" class="rg-note">{{ data.consumption.catatan }}</p>
                    </div>
                </section>

                <p class="rg-foot">
                    <template v-if="data.public">Halaman baca-saja · no. HP disamarkan · diperbarui otomatis. Isi petugas dari aplikasi <a href="/crew/app">GTR Crew</a> dengan akun panitia.</template>
                    <template v-else-if="!canFill">Akun ini hanya bisa melihat. Pengisian petugas: akun panitia (koordinator) atau admin.</template>
                    <template v-else>Isian tersimpan otomatis &amp; tampil di perangkat lain dalam beberapa detik. Arahkan kursor ke slot untuk melihat siapa yang terakhir mengubah.<template v-if="canRename"> Ketuk ikon pensil untuk mengganti nama area/kelompok, atau mengubah pos (nama, jumlah orang, jobdesk).</template></template>
                </p>
            </main>
        </template>
        </template>

        <!-- Ubah area / kelompok (nama) atau pos (nama, jumlah orang, jobdesk) — admin -->
        <div v-if="ren" class="rg-modal" role="dialog" aria-modal="true" :aria-label="ren.type === 'positions' ? 'Ubah pos' : `Ganti nama ${ren.label}`">
            <form class="rg-modal-card" @submit.prevent="saveRename(false)">
                <h3>{{ ren.type === 'positions' ? 'Ubah pos' : `Ganti nama ${ren.label}` }}</h3>
                <label class="rg-field"><span>{{ ren.type === 'positions' ? 'Nama pos' : `Nama ${ren.label}` }}</span>
                    <input v-model="ren.nama" class="rg-in rg-ren" :maxlength="ren.type === 'positions' ? 120 : 80" autocomplete="off">
                </label>
                <template v-if="ren.type === 'positions'">
                    <div class="rg-field"><span>Jumlah orang</span>
                        <div class="rg-steps">
                            <div class="rg-step">
                                <small>Minimal</small>
                                <div><button type="button" aria-label="Kurangi minimal" @click="step('min', -1)">−</button><b class="num">{{ ren.min }}</b><button type="button" aria-label="Tambah minimal" @click="step('min', 1)">+</button></div>
                            </div>
                            <div class="rg-step">
                                <small>Maksimal</small>
                                <div><button type="button" aria-label="Kurangi maksimal" @click="step('max', -1)">−</button><b class="num">{{ ren.max }}</b><button type="button" aria-label="Tambah maksimal" @click="step('max', 1)">+</button></div>
                            </div>
                        </div>
                        <small class="rg-hint">Slot isian = maksimal. Minimal 0 = pos opsional. Sekarang terisi {{ ren.filled }} orang.</small>
                    </div>
                    <label class="rg-field"><span>Jobdesk</span>
                        <textarea v-model="ren.deskripsi" class="rg-in rg-area-in" rows="4" maxlength="1000" placeholder="Tugas pos ini, mis. jaga titik, arahkan peserta, lapor ke Koordinator Lapangan."></textarea>
                    </label>
                </template>
                <div v-if="ren.error" class="rg-alert">
                    {{ ren.error }}
                    <button v-if="ren.canCompact" type="button" class="rg-btn primary rg-compact" :disabled="ren.busy" @click="saveRename(true)">Pindahkan ke slot kosong &amp; simpan</button>
                </div>
                <div class="rg-modal-actions">
                    <button type="button" class="rg-btn" :disabled="ren.busy" @click="ren = null">Batal</button>
                    <button type="submit" class="rg-btn primary" :disabled="ren.busy">{{ ren.busy ? 'Menyimpan…' : 'Simpan' }}</button>
                </div>
                <p class="rg-note">Tambah/hapus pos atau kelompok tetap di panel admin → Roster Panitia.</p>
            </form>
        </div>

        <!-- Salin manual bila clipboard gagal -->
        <div v-if="copyBox !== null" class="rg-modal" role="dialog" aria-modal="true" aria-label="Salin ringkasan">
            <div class="rg-modal-card">
                <h3>Salin ringkasan WA</h3>
                <p>Clipboard tidak tersedia. Pilih semua teks di bawah lalu salin manual.</p>
                <textarea class="rg-copy" readonly :value="copyBox" @focus="$event.target.select()"></textarea>
                <div class="rg-modal-actions"><button class="rg-btn primary" @click="copyBox = null">Tutup</button></div>
            </div>
        </div>

        <!-- Import JSON dengan preview -->
        <div v-if="imp" class="rg-modal" role="dialog" aria-modal="true" aria-label="Import JSON">
            <div class="rg-modal-card wide">
                <h3>Import JSON roster</h3>
                <div class="rg-modes">
                    <label :class="{ on: imp.mode === 'petugas' }"><input v-model="imp.mode" type="radio" value="petugas" @change="runPreview">
                        <span><b>Petugas saja</b><small>Pos dicocokkan dari ID / nama. Slot di file menimpa slot yang sama; slot lain tidak diubah.</small></span></label>
                    <label :class="{ on: imp.mode === 'struktur', off: !isAdmin }"><input v-model="imp.mode" type="radio" value="struktur" :disabled="!isAdmin" @change="runPreview">
                        <span><b>Struktur + petugas</b><small>{{ isAdmin ? 'Struktur disamakan dengan file (pos yang tidak ada di file dihapus), petugas diganti isi file.' : 'Khusus admin.' }}</small></span></label>
                </div>
                <label class="rg-file"><input type="file" accept="application/json,.json" @change="pickFile"><span>{{ imp.name ?? 'Pilih file export (.json)…' }}</span></label>
                <p v-if="imp.error" class="rg-alert">{{ imp.error }}</p>
                <p v-if="imp.busy" class="rg-note">Memproses…</p>
                <template v-if="imp.preview">
                    <p class="rg-prev-sum">
                        <span v-for="(n, k) in imp.preview.summary" :key="k" :class="'chg-' + k">{{ CHANGE_LABEL[k] }} <b class="num">{{ n }}</b></span>
                    </p>
                    <ul v-if="imp.preview.warnings.length" class="rg-warns"><li v-for="(w, i) in imp.preview.warnings" :key="i">{{ w }}</li></ul>
                    <ul class="rg-changes">
                        <li v-for="(c, i) in imp.preview.changes.slice(0, 300)" :key="i" :class="'chg-' + c.type">
                            <em>{{ CHANGE_LABEL[c.type] }}</em><span>{{ c.target }}</span>
                            <small v-if="c.from || c.to">{{ c.from ?? '—' }} → {{ c.to ?? '—' }}</small>
                        </li>
                        <li v-if="!imp.preview.changes.length" class="none">Tidak ada perubahan.</li>
                        <li v-if="imp.preview.changes.length > 300" class="none">…dan {{ imp.preview.changes.length - 300 }} perubahan lain.</li>
                    </ul>
                </template>
                <div class="rg-modal-actions">
                    <button class="rg-btn" :disabled="imp.busy" @click="imp = null">Batal</button>
                    <button class="rg-btn primary" :disabled="imp.busy || !imp.preview?.changes.length" @click="applyImport">Simpan perubahan</button>
                </div>
            </div>
        </div>

        <div v-if="toast" class="rg-toast" role="status">{{ toast }}</div>
    </div>
</template>
