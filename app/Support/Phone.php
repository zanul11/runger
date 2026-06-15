<?php

namespace App\Support;

class Phone
{
    /**
     * Normalisasi nomor HP Indonesia ke format kanonik "62XXXXXXXXXX".
     * Menerima input apa pun: +62…, 62…, 081…, 81…, dengan spasi/strip/titik.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // Sisakan angka saja (buang +, spasi, strip, titik, kurung).
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return $raw;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        // mis. "81234…" → "6281234…"
        return '62' . $digits;
    }
}
