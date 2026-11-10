/**
 * Validasi OFFLINE di scanner (fungsi murni — diuji di tests/js/logic.test.mjs).
 * Server memvalidasi ulang dengan aturan yang sama; ini hanya untuk umpan balik instan.
 */
import { normalizeBib, normalizeRegistrationNumber } from './bib.js';

export const RESULT = {
    OK: 'ok',
    ALREADY: 'already',          // sudah discan di pos ini (device ini atau device lain)
    WRONG_CATEGORY: 'wrong',     // kategori peserta tidak melewati pos ini
    UNKNOWN: 'unknown',          // BIB tidak terdaftar
    INVALID: 'invalid',          // input bukan nomor BIB
};

/** Jenis umpan balik (suara/getar) per hasil. */
export function feedbackKind(result) {
    return {
        [RESULT.OK]: 'ok',
        [RESULT.ALREADY]: 'already',
        [RESULT.WRONG_CATEGORY]: 'wrong',
    }[result] ?? 'unknown';
}

/** Jarak minimum antar "lewat" di pos yang sama — sama dengan App\\Support\\Passes::GAP_MS. */
export const PASS_GAP_MS = 600_000; // 10 menit

/**
 * Kelompokkan waktu scan di satu pos menjadi "lewat" — identik dengan Passes::cluster() (PHP).
 * Scan pertama = lewat ke-1; scan berikutnya jadi lewat baru hanya bila ≥ gap dari lewat sebelumnya.
 */
export function clusterPasses(timesMs, gapMs = PASS_GAP_MS) {
    const passes = [];
    for (const t of [...timesMs].sort((a, b) => a - b)) {
        if (!passes.length || t - passes[passes.length - 1] >= gapMs) passes.push(t);
    }
    return passes;
}

/** Scan lokal yang dihitung (tidak dibatalkan, tidak ditolak — kecuali ditolak karena duplikat). */
function countedScanTimes(localScans) {
    return localScans
        .filter((s) => !s.cancelled && !(s.status === 'rejected' && s.reason !== 'duplicate')
            && s.local_result !== RESULT.WRONG_CATEGORY)
        .map((s) => s.device_time_ms + s.clock_offset_ms);
}

/** Waktu tiap lewat di pos ini: gabungan scan lokal (HP ini) + lewat yang sudah tercatat di server. */
export function passTimes(localScans, serverPassesMs = [], gapMs = PASS_GAP_MS) {
    return clusterPasses([...countedScanTimes(localScans), ...serverPassesMs], gapMs);
}

/**
 * Klasifikasi input timing.
 *
 * @param {object} p
 * @param {string|null} p.bib            BIB kanonik (hasil normalizeBib)
 * @param {object|null} p.participant    baris roster untuk bib tsb
 * @param {number[]} p.pointCategoryIds  kategori yang melewati pos ini
 * @param {number[]} p.passes            waktu lewat yang sudah tercatat di pos ini (urut)
 * @param {number} p.visits              berapa kali kategori peserta melewati pos ini (15K di Pertigaan = 2)
 * @param {number} p.nowMs               waktu terkoreksi sekarang
 * @returns {{result: string, visit: number|null}}  visit = lewat ke-berapa bila OK
 */
export function classifyTiming({ bib, participant, pointCategoryIds, passes = [], visits = 1, nowMs = Date.now(), gapMs = PASS_GAP_MS }) {
    if (bib === null) return { result: RESULT.INVALID, visit: null };
    if (!participant) return { result: RESULT.UNKNOWN, visit: null };
    if (!pointCategoryIds.includes(participant.category_id)) return { result: RESULT.WRONG_CATEGORY, visit: null };

    const n = passes.length;
    if (n === 0) return { result: RESULT.OK, visit: 1 };
    // Lewat berikutnya yang memang ada di rute kategori (mis. 15K: WS2) dan sudah ≥ 10 menit.
    if (n < visits && nowMs - passes[n - 1] >= gapMs) return { result: RESULT.OK, visit: n + 1 };

    return { result: RESULT.ALREADY, visit: null };
}

/**
 * Waktu scan sah paling awal di pos ini untuk satu BIB: dari antrean lokal (tidak dibatalkan,
 * tidak ditolak server) atau `seen_at_ms` roster (scan dari device lain yang sudah tersinkron).
 */
export function previousPass(localScans, seenAtMs) {
    const times = countedScanTimes(localScans);
    if (seenAtMs != null) times.push(seenAtMs);

    return times.length ? Math.min(...times) : null;
}

/** Kode racepack: nomor registrasi e-ticket ("GTR…") atau nomor BIB. */
export function parseRacepackCode(raw) {
    const regNo = normalizeRegistrationNumber(raw);
    if (regNo) return { type: 'registration', value: regNo };

    const bib = normalizeBib(raw);

    return bib ? { type: 'bib', value: bib } : null;
}

/**
 * Item antrean (scan / racepack) milik user yang sedang login? Dipakai untuk daftar di layar
 * dan untuk sinkronisasi — scan marshal lain di HP yang sama tidak tampil & tidak dikirim
 * dengan token user ini (supaya `scanned_by` di server tetap benar).
 * Item lama tanpa user_id (sebelum fitur ini) dianggap milik siapa pun yang login.
 */
export function belongsTo(item, userId) {
    return item.user_id == null || item.user_id === userId;
}

/** Tunda berikutnya untuk sinkronisasi: 10 dtk normal, eksponensial saat gagal, maks 5 menit. */
export function nextSyncDelay(failures, baseMs = 10_000, maxMs = 300_000) {
    return failures <= 0 ? baseMs : Math.min(baseMs * 2 ** failures, maxMs);
}
