import { test } from 'node:test';
import assert from 'node:assert/strict';
import { consumption, filledCount, matches, posStatus, rosterCsv, shortfall, totals, whatsappText } from '../../resources/js/roster/logic.js';

const slot = (nama = '', no_hp = '') => ({ nama, no_hp, catatan: '' });
const pos = (over = {}) => ({ id: 1, nama: 'Marshal 3', min: 2, max: 2, km: ['2', '8,1', '12,8'], slots: [slot(), slot()], ...over });

function data() {
    return {
        event: { nama: 'GTR 2026', tanggal_label: 'Minggu, 29 November 2026' },
        areas: [
            { id: 1, nama: 'Race Village', slug: 'race-village', groups: [{ nama: 'Komando', positions: [
                pos({ id: 10, nama: 'Race Director', min: 1, max: 1, km: [], slots: [slot('Budi', '0812')] }),
                pos({ id: 11, nama: 'Timing', min: 2, max: 3, km: [], slots: [slot('A'), slot(), slot()] }),
            ] }] },
            { id: 2, nama: 'Lapangan', slug: 'lapangan', groups: [{ nama: 'Marshal', positions: [
                pos({ id: 20 }),
                pos({ id: 21, nama: 'Opsional', min: 0, max: 2, km: [], slots: [slot(), slot()] }),
            ] }] },
        ],
        consumption: { tamu_tambahan: 10, air_per_orang: 2, jadwal: [{ waktu: '04.00', kegiatan: 'Sarapan' }], area_acuan_id: null },
    };
}

test('status & kekurangan per pos', () => {
    assert.equal(posStatus(pos()), 'empty');
    assert.equal(posStatus(pos({ slots: [slot('X'), slot()] })), 'part');
    assert.equal(posStatus(pos({ slots: [slot('X'), slot('Y')] })), 'ok');
    assert.equal(posStatus(pos({ min: 0, slots: [slot(), slot()] })), 'ok');
    assert.equal(shortfall(pos({ slots: [slot('X'), slot('  ')] })), 1);
    // slot di atas max tidak dihitung
    assert.equal(filledCount(pos({ max: 1, slots: [slot('X'), slot('Y')] })), 1);
});

test('total: kekurangan dijumlah per pos', () => {
    const d = data();
    const t = totals(d.areas.flatMap((a) => a.groups.flatMap((g) => g.positions)));
    assert.deepEqual([t.filled, t.min, t.max, t.short], [2, 5, 8, 3]);
});

test('pencarian KM: 8,1 / 8.1 / km 8,1 / 8', () => {
    const p = pos();
    for (const q of ['8,1', '8.1', 'km 8,1', 'KM 8.1', '8', 'km 12']) assert.ok(matches(p, null, null, q), q);
    assert.ok(!matches(p, null, null, 'km 5'));
    assert.ok(!matches(p, null, null, '8,2'));
    assert.ok(matches(pos({ slots: [slot('Wahab')] }), null, null, 'wahab'));
    assert.ok(matches(p, { nama: 'Marshal' }, null, 'marshal'));
});

test('konsumsi: dasar = max(kebutuhan maks, terisi) + tamu; pos lapangan dari slug', () => {
    const c = consumption(data());
    assert.equal(c.dasar, 8 + 10);
    assert.equal(c.air, 36);
    assert.equal(c.lapangan, 4);
    assert.equal(consumption(data(), 0).dasar, 8);
});

test('ringkasan WA memuat format yang diminta', () => {
    const txt = whatsappText(data());
    assert.match(txt, /^\*ROSTER PANITIA HARI H — GTR 2026\*/);
    assert.match(txt, /_Komando_\n\*Race Director\* — 1\/1\n• Budi \(0812\)/);
    assert.match(txt, /\*Marshal 3\* \(KM 2 · 8,1 · 12,8\) — 0\/2\n• _\(belum ada\)_\n• _\(belum ada\)_\n⚠️ kurang 2 orang/);
    assert.match(txt, /\*KONSUMSI RACE VILLAGE\*/);
    assert.match(txt, /• 04.00 — Sarapan/);
});

test('CSV satu baris per slot', () => {
    const lines = rosterCsv(data()).split('\r\n');
    assert.equal(lines.length, 1 + 1 + 3 + 2 + 2);
    assert.ok(lines[1].includes('Race Director;;1;1;wajib;Budi;0812'));
});
