/**
 * Normalisasi nomor BIB — HARUS identik dengan app/Support/Bib.php.
 * Kasus uji bersama: tests/Fixtures/bib-normalize-cases.json (tests/js/bib.test.mjs & tests/Unit/BibTest.php).
 *
 *  1. buang spasi (spasi, tab, baris baru, NBSP, BOM) — termasuk di tengah
 *  2. tolak bila ada karakter selain 0-9
 *  3. buang nol di depan ("07001" → "7001")
 *  4. tolak bila kosong / hanya nol / lebih dari BIB_MAX_LENGTH digit
 */
export const BIB_MAX_LENGTH = 8;

// Sama dengan Bib::WHITESPACE di PHP.
const WHITESPACE = /[ \t\n\r\f\v ﻿]+/g;

export function normalizeBib(input) {
    if (typeof input === 'number') {
        if (!Number.isSafeInteger(input)) return null;
        input = String(input);
    }
    if (typeof input !== 'string') return null;

    let s = input.replace(WHITESPACE, '');
    if (s === '' || !/^[0-9]+$/.test(s)) return null;

    s = s.replace(/^0+/, '');
    if (s === '' || s.length > BIB_MAX_LENGTH) return null;

    return s;
}

/** Nomor registrasi e-ticket ("GTR202600123") — hanya untuk mode racepack. */
export function normalizeRegistrationNumber(input) {
    if (typeof input !== 'string') return null;
    const s = input.replace(WHITESPACE, '').toUpperCase();

    return /^GTR[0-9]+$/.test(s) ? s : null;
}
