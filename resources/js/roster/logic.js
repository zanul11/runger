/**
 * Roster panitia hari H — perhitungan murni (tanpa Vue / DOM), dipakai GTR Crew (/crew → Roster)
 * dan halaman publik /crew/roster. Semua total dihitung dari data pos & slot.
 * Tes: tests/js/roster.test.mjs
 */

export const isFilled = (slot) => !!String(slot?.nama ?? '').trim();

export function filledCount(pos) {
    return (pos.slots ?? []).slice(0, pos.max).filter(isFilled).length;
}

/** ok = terisi ≥ min (min 0 selalu ok) · part = 0 < terisi < min · empty = kosong. */
export function posStatus(pos) {
    const n = filledCount(pos);
    if (pos.min <= 0 || n >= pos.min) return 'ok';
    return n > 0 ? 'part' : 'empty';
}

export function shortfall(pos) {
    return Math.max(0, pos.min - filledCount(pos));
}

/** Angka "butuh" di pill terisi/butuh: kebutuhan minimal (pos opsional: maksimal). */
export const needOf = (pos) => (pos.min > 0 ? pos.min : pos.max);

export function positionsOf(area) {
    return area.groups.flatMap((g) => g.positions);
}

/** Total sekumpulan pos. Kekurangan = jumlah kekurangan per pos (kelebihan di satu pos tidak menutup pos lain). */
export function totals(positions) {
    const t = { filled: 0, min: 0, max: 0, short: 0, count: positions.length, full: 0 };
    for (const p of positions) {
        const n = filledCount(p);
        t.filled += n;
        t.min += p.min;
        t.max += p.max;
        t.short += shortfall(p);
        if (posStatus(p) === 'ok') t.full++;
    }
    return t;
}

export const range = (min, max) => (min === max ? `${min}` : `${min}–${max}`);

// ---------------------------------------------------------------- pencarian

/** "KM 8.1" → "km 8,1" (titik = koma, spasi dirapikan). */
export function norm(s) {
    return String(s ?? '').toLowerCase().replace(/(\d)\.(\d)/g, '$1,$2').replace(/\s+/g, ' ').trim();
}

/**
 * Cocokkan pos dengan kata kunci: nama pos, grup, area, deskripsi, KM ("8,1", "8.1", "km 8,1"),
 * atau nama / no. HP petugas.
 */
export function matches(pos, group, area, query) {
    const q = norm(query);
    if (!q) return true;
    const kmQ = q.replace(/^km\s*/, '');
    const isKm = /^\d+(,\d*)?$/.test(kmQ);
    if (isKm) {
        // "8,1" → KM 8,1 (juga 8,15); "8" → semua KM 8,x.
        const prefix = kmQ.includes(',') ? kmQ : `${kmQ},`;
        if ((pos.km ?? []).some((k) => norm(k) === kmQ || norm(k).startsWith(prefix))) return true;
        // Diawali "km": khusus KM, jangan cocokkan teks lain (mis. no. HP).
        if (q.startsWith('km')) return false;
    }
    const hay = [pos.nama, group?.nama, area?.nama, pos.deskripsi, pos.catatan_tambahan, ...(pos.km ?? []).map((k) => `km ${k}`)]
        .concat((pos.slots ?? []).flatMap((s) => [s.nama, s.no_hp]))
        .map(norm).join(' | ');
    return hay.includes(q);
}

// ---------------------------------------------------------------- konsumsi

/**
 * Dasar = max(total kebutuhan maksimal pos aktif, total terisi) + tamu.
 * Paket sarapan pos lapangan = total kebutuhan maksimal area acuan (default slug "lapangan").
 */
export function consumption(data, tamu = data?.consumption?.tamu_tambahan ?? 0) {
    const areas = data?.areas ?? [];
    const all = totals(areas.flatMap(positionsOf));
    const c = data?.consumption ?? {};
    const ref = areas.find((a) => a.id === c.area_acuan_id) ?? areas.find((a) => a.slug === 'lapangan');
    const tamuN = Math.max(0, parseInt(tamu, 10) || 0);
    const panitia = Math.max(all.max, all.filled);
    const dasar = panitia + tamuN;
    return {
        panitia,
        tamu: tamuN,
        dasar,
        sarapan: dasar,
        makanSiang: dasar,
        snack: dasar,
        air: dasar * (c.air_per_orang ?? 2),
        airPerOrang: c.air_per_orang ?? 2,
        lapangan: ref ? totals(positionsOf(ref)).max : 0,
        lapanganNama: ref?.nama ?? null,
        basisLabel: all.filled > all.max ? 'total terisi' : 'kebutuhan maks.',
    };
}

// ---------------------------------------------------------------- ringkasan WhatsApp

export function kmLabel(pos) {
    return pos.km?.length ? `KM ${pos.km.join(' · ')}` : '';
}

export function whatsappText(data, tamu) {
    const e = data.event ?? {};
    const areas = data.areas ?? [];
    const all = totals(areas.flatMap(positionsOf));
    const L = [];
    L.push(`*ROSTER PANITIA HARI H — ${(e.nama ?? '').toUpperCase()}*`);
    if (e.tanggal_label) L.push(`📅 ${e.tanggal_label}`);
    if (e.lokasi_race_village) L.push(`🏁 Race Village: ${e.lokasi_race_village}`);
    if (e.lokasi_rute) L.push(`⛰️ Rute: ${e.lokasi_rute}`);
    if (e.kategori) L.push(`🏃 Kategori: ${e.kategori}`);
    if (e.catatan_open_gate) L.push(`⏰ ${e.catatan_open_gate}`);
    L.push('');
    L.push(`*Total: ${all.filled} terisi dari ${range(all.min, all.max)} orang*${all.short ? ` — ⚠️ kurang ${all.short} dari minimal` : ' ✓'}`);
    for (const a of areas) {
        const t = totals(positionsOf(a));
        L.push(`• ${a.nama}: ${t.filled} terisi dari ${range(t.min, t.max)}${t.short ? ` (kurang ${t.short})` : ' ✓'}`);
    }
    for (const a of areas) {
        const t = totals(positionsOf(a));
        L.push('', '━━━━━━━━━━━━', `*${a.nama.toUpperCase()}* — ${t.filled}/${range(t.min, t.max)}`);
        for (const g of a.groups) {
            if (!g.positions.length) continue;
            L.push('', `_${g.nama}_`);
            for (const p of g.positions) {
                const km = kmLabel(p);
                L.push(`*${p.nama}*${km ? ` (${km})` : ''} — ${filledCount(p)}/${needOf(p)}`);
                const filled = p.slots.slice(0, p.max).filter(isFilled);
                for (const s of filled) L.push(`• ${s.nama.trim()}${s.no_hp ? ` (${s.no_hp})` : ''}`);
                const missing = Math.max(p.min - filled.length, filled.length ? 0 : 1);
                for (let i = 0; i < missing; i++) L.push('• _(belum ada)_');
                if (shortfall(p)) L.push(`⚠️ kurang ${shortfall(p)} orang`);
            }
        }
    }
    const c = consumption(data, tamu);
    L.push('', '━━━━━━━━━━━━', '*KONSUMSI RACE VILLAGE*');
    L.push(`Dasar hitungan: ${c.dasar} orang (${c.panitia} panitia — ${c.basisLabel} + ${c.tamu} tamu/VIP/sponsor)`);
    L.push(`• Porsi sarapan: ${c.sarapan}`);
    L.push(`• Makan siang: ${c.makanSiang}`);
    L.push(`• Snack box: ${c.snack}`);
    L.push(`• Air mineral: ${c.air} (${c.airPerOrang}/orang)`);
    if (c.lapanganNama) L.push(`• Paket sarapan ke pos ${c.lapanganNama.toLowerCase()}: ${c.lapangan}`);
    const jadwal = data.consumption?.jadwal ?? [];
    if (jadwal.length) {
        L.push('', '*Jadwal konsumsi*');
        for (const j of jadwal) L.push(`• ${j.waktu} — ${j.kegiatan}`);
    }
    if (data.consumption?.catatan) L.push(`_${data.consumption.catatan}_`);
    return L.join('\n');
}

// ---------------------------------------------------------------- CSV (Excel)

/** CSV pemisah ";" (Excel lokal Indonesia) + BOM UTF-8. Satu baris per slot. */
export function rosterCsv(data) {
    const esc = (v) => {
        const s = String(v ?? '');
        return /[";\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    const rows = [['Area', 'Grup', 'Pos', 'KM', 'Kebutuhan', 'Slot', 'Status slot', 'Nama', 'No. HP', 'Catatan']];
    for (const a of data.areas ?? []) {
        for (const g of a.groups) {
            for (const p of g.positions) {
                p.slots.slice(0, p.max).forEach((s, i) => {
                    rows.push([a.nama, g.nama, p.nama, (p.km ?? []).join(' · '), range(p.min, p.max), i + 1,
                        i < p.min ? 'wajib' : 'opsional', s.nama, s.no_hp, s.catatan]);
                });
            }
        }
    }
    return '﻿' + rows.map((r) => r.map(esc).join(';')).join('\r\n');
}
