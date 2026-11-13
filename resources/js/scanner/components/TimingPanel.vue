<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { state, recordScan, cancelScan, recentScans, categoryName, RESULT } from '../store.js';
import { syncSoon } from '../sync.js';
import { feedback } from '../feedback.js';
import { feedbackKind } from '../logic.js';
import { formatTime } from '../clock.js';
import Icon from './Icon.vue';
import Keypad from './Keypad.vue';
import QrReader from './QrReader.vue';

const CARD_MS = 2000;

const inputMode = ref(readMode());
const typed = ref('');
const card = ref(null);
const last = ref(null);   // scan terakhir (untuk tombol batalkan)
const recent = ref([]);
let hideTimer;
let poll;

const VIEW = {
    [RESULT.OK]: { title: 'Tercatat', icon: 'check' },
    [RESULT.ALREADY]: { title: 'Sudah discan', icon: 'repeat' },
    [RESULT.WRONG_CATEGORY]: { title: 'Salah pos / kategori', icon: 'alert' },
    [RESULT.UNKNOWN]: { title: 'BIB tidak terdaftar', icon: 'x' },
    [RESULT.INVALID]: { title: 'BIB tidak valid', icon: 'x' },
};
const GENDER = { M: 'Laki-laki', F: 'Perempuan' };
const SYNC_TEXT = {
    duplicate: 'duplikat', cancelled: 'dibatalkan', unknown_bib: 'tak terdaftar', invalid_bib: 'tak valid',
    wrong_category: 'salah kategori', wrong_post: 'salah pos', invalid: 'data rusak', before_reset: 'data uji',
};

function readMode() {
    try { return localStorage.getItem('gtr-input-mode') === 'qr' ? 'qr' : 'keypad'; } catch { return 'keypad'; }
}
function setMode(m) {
    inputMode.value = m;
    try { localStorage.setItem('gtr-input-mode', m); } catch { /* abaikan */ }
}

async function loadRecent() {
    recent.value = await recentScans(6);
}

async function submit(raw, source) {
    const { scan, result, participant, passes, visit, visitList } = await recordScan(raw, source);
    const kind = feedbackKind(result);
    feedback(kind);

    const multi = visitList.length > 1; // kategori ini lewat pos ini >1 kali (15K di Pertigaan)
    const v = multi && visit ? visitList[visit - 1] : null;
    const view = { ...VIEW[result] };
    if (result === RESULT.OK && v) view.title = `Tercatat · lewat ke-${visit}`;

    card.value = {
        key: scan.uuid,
        tone: kind,
        ...view,
        bib: participant?.bib ?? String(raw).trim(),
        name: participant?.name ?? null,
        tags: participant ? [categoryName(participant.category_id), GENDER[participant.gender], v ? `${v.code} · km ${v.km}` : null].filter(Boolean) : [],
        note: result === RESULT.ALREADY ? alreadyNote(passes, visitList)
            : result === RESULT.WRONG_CATEGORY ? `Kategori ini tidak melewati ${state.session.timing_point.code}`
                : result === RESULT.UNKNOWN ? 'Cek lagi nomor di BIB pelari' : null,
    };
    last.value = scan;
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => { card.value = null; }, CARD_MS);

    await loadRecent();
    syncSoon();
}

async function undo() {
    if (!last.value || last.value.cancelled) return;
    if (!confirm(`Batalkan scan BIB ${last.value.bib}?`)) return;
    await cancelScan(last.value.uuid);
    last.value = { ...last.value, cancelled: true };
    card.value = null;
    feedback('already');
    await loadRecent();
    syncSoon();
}

/** Keterangan "sudah discan": jam lewat sebelumnya, atau kapan lewat berikutnya baru dihitung. */
function alreadyNote(passes, visitList) {
    const last = passes[passes.length - 1];
    if (passes.length < visitList.length) {
        const min = Math.max(0, Math.floor((Date.now() + (state.clock.offsetMs || 0) - last) / 60_000));
        const next = visitList[passes.length];
        return `Baru ${min} menit sejak ${formatTime(last)} · ${next.code} dihitung setelah ≥ 10 menit`;
    }
    return passes.length > 1
        ? `Sudah lewat ${passes.length}× · terakhir ${formatTime(last)} · tetap dicatat`
        : `Pertama lewat ${formatTime(last)} · tetap dicatat`;
}

function syncText(s) {
    if (s.cancelled) return s.status === 'pending' ? 'batal · antre' : 'dibatalkan';
    if (s.status === 'pending') return 'antre';
    if (s.status === 'synced') return 'terkirim';
    return SYNC_TEXT[s.reason] ?? 'ditolak';
}
function historyIcon(s) {
    return { ok: 'check', already: 'repeat', wrong: 'alert' }[s.local_result] ?? 'x';
}

onMounted(() => {
    loadRecent();
    poll = setInterval(loadRecent, 5000); // status kirim berubah di latar belakang
});
onBeforeUnmount(() => {
    clearInterval(poll);
    clearTimeout(hideTimer);
});
</script>

<template>
    <main class="panel">
        <!-- Area kerja = tepat satu layar: stage mengisi sisa ruang, keypad selalu terlihat di bawah -->
        <div class="work" :class="{ 'is-qr': inputMode === 'qr' }">
            <!-- Mode kamera: kamera paling atas (sejajar pandangan saat HP diarahkan ke BIB), hasil di bawahnya -->
            <QrReader v-if="inputMode === 'qr'" @decode="submit($event, 'qr')" />

            <section class="stage" aria-live="assertive">
                <div v-if="card" :key="card.key" class="result" :class="card.tone" @click="card = null">
                    <div class="result-icon"><Icon :name="card.icon" :size="30" :stroke="3" /></div>
                    <div class="result-title">{{ card.title }}</div>
                    <div class="result-body">
                        <div class="result-bib num">{{ card.bib }}</div>
                        <div v-if="card.name" class="result-name">{{ card.name }}</div>
                        <div v-if="card.tags.length" class="result-tags"><span v-for="t in card.tags" :key="t" class="tag">{{ t }}</span></div>
                        <div v-if="card.note" class="result-note">{{ card.note }}</div>
                    </div>
                    <div class="result-timer" :style="{ animationDuration: CARD_MS + 'ms' }"></div>
                </div>
                <div v-else-if="typed" class="stage-typing">
                    <small>NOMOR BIB</small>
                    <b class="num">{{ typed }}<span class="caret"></span></b>
                </div>
                <div v-else class="stage-idle">
                    <Icon :name="inputMode === 'qr' ? 'camera' : 'keypad'" :size="30" />
                    <span class="hint">{{ inputMode === 'qr' ? 'Hasil scan muncul di sini' : 'Ketik nomor BIB pelari' }}</span>
                </div>
            </section>

            <div v-if="last" class="lastbar">
                <div class="info">
                    <small>Terakhir</small>
                    <b class="num">{{ last.bib }}</b>
                    <span class="who">{{ last.cancelled ? 'Dibatalkan' : (last.name ?? 'Tidak terdaftar') }}</span>
                </div>
                <button class="btn danger sm" :disabled="last.cancelled" @click="undo"><Icon name="undo" :size="18" /> Batalkan</button>
            </div>

            <div class="seg" role="tablist" aria-label="Cara input">
                <button role="tab" :class="{ on: inputMode === 'keypad' }" @click="setMode('keypad')"><Icon name="keypad" :size="18" /> Keypad</button>
                <button role="tab" :class="{ on: inputMode === 'qr' }" @click="setMode('qr')"><Icon name="camera" :size="18" /> Kamera QR</button>
            </div>

            <Keypad v-if="inputMode === 'keypad'" v-model="typed" @submit="submit($event, 'manual')" @typing="card = null" />
        </div>

        <div class="section-title"><span>Riwayat saya</span><Icon name="chevron-right" :size="16" :stroke="2.5" class="down" /></div>
        <ul v-if="recent.length" class="history">
            <li v-for="s in recent" :key="s.uuid" :class="[s.local_result, { cancelled: s.cancelled }]">
                <span class="st"><Icon :name="historyIcon(s)" :size="18" :stroke="2.75" /></span>
                <b class="num">{{ s.bib }}</b>
                <span class="who">
                    <span>{{ s.name ?? 'Tidak terdaftar' }}</span>
                    <small class="num">{{ formatTime(s.device_time_ms + s.clock_offset_ms) }}</small>
                </span>
                <span class="sync" :class="s.cancelled ? '' : s.status">{{ syncText(s) }}</span>
            </li>
        </ul>
        <p v-else class="empty">Belum ada scan.</p>
    </main>
</template>
