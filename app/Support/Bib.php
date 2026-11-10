<?php

namespace App\Support;

/**
 * Normalisasi nomor BIB — HARUS identik dengan resources/js/scanner/bib.js.
 * Kasus uji bersama: tests/Fixtures/bib-normalize-cases.json (dipakai test PHP & JS).
 *
 * Format BIB di database (BibNumberService): prefix kategori (bukan 0) + urutan 3 digit,
 * mis. 7001, 5158. Jadi BIB tidak pernah diawali nol, dan bentuk kanonik = tanpa nol di depan.
 *
 *  1. buang semua spasi (termasuk di tengah: "70 01" → "7001")
 *  2. tolak bila ada karakter selain 0-9
 *  3. buang nol di depan ("07001" → "7001")
 *  4. tolak bila kosong / hanya nol / lebih dari MAX_LENGTH digit
 */
final class Bib
{
    public const MAX_LENGTH = 8;

    /** Sama dengan WHITESPACE di resources/js/scanner/bib.js. */
    private const WHITESPACE = '/[ \t\n\r\f\v\x{00A0}\x{FEFF}]+/u';

    public static function normalize(mixed $input): ?string
    {
        if ($input === null || is_bool($input) || is_array($input) || is_float($input)) {
            return null;
        }

        // Daftar spasi eksplisit (bukan \s) supaya identik dengan JS: spasi, tab, baris baru, NBSP, BOM.
        $s = preg_replace(self::WHITESPACE, '', (string) $input);

        if ($s === null || $s === '' || ! preg_match('/^[0-9]+$/', $s)) {
            return null;
        }

        $s = ltrim($s, '0');

        if ($s === '' || strlen($s) > self::MAX_LENGTH) {
            return null;
        }

        return $s;
    }

    /** Nomor registrasi e-ticket (isi QR e-ticket), mis. "GTR202600123". Hanya untuk mode racepack. */
    public static function normalizeRegistrationNumber(mixed $input): ?string
    {
        if (! is_string($input)) {
            return null;
        }

        $s = strtoupper(preg_replace(self::WHITESPACE, '', $input) ?? '');

        return preg_match('/^GTR[0-9]+$/', $s) ? $s : null;
    }
}
