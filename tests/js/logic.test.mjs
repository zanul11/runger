import { test } from 'node:test';
import assert from 'node:assert/strict';
import { classifyTiming, previousPass, parseRacepackCode, nextSyncDelay, feedbackKind, belongsTo, clusterPasses, passTimes, PASS_GAP_MS, RESULT } from '../../resources/js/scanner/logic.js';
import { readFileSync } from 'node:fs';
import { offsetFrom } from '../../resources/js/scanner/clock.js';

const budi = { bib: '7001', category_id: 1, name: 'Budi' };

test('classifyTiming', () => {
    const base = { bib: '7001', participant: budi, pointCategoryIds: [1], passes: [], visits: 1, nowMs: 10_000_000 };
    assert.deepEqual(classifyTiming(base), { result: RESULT.OK, visit: 1 });
    assert.equal(classifyTiming({ ...base, bib: null }).result, RESULT.INVALID);
    assert.equal(classifyTiming({ ...base, participant: null }).result, RESULT.UNKNOWN);
    assert.equal(classifyTiming({ ...base, pointCategoryIds: [2] }).result, RESULT.WRONG_CATEGORY);
    assert.equal(classifyTiming({ ...base, passes: [123] }).result, RESULT.ALREADY);
    // salah kategori lebih penting daripada "sudah discan"
    assert.equal(classifyTiming({ ...base, pointCategoryIds: [2], passes: [123] }).result, RESULT.WRONG_CATEGORY);
});

test('classifyTiming: pos yang dilewati 2x (15K di Pertigaan)', () => {
    const now = 10_000_000;
    const base = { bib: '5001', participant: { bib: '5001', category_id: 2 }, pointCategoryIds: [2], visits: 2, nowMs: now };
    assert.deepEqual(classifyTiming({ ...base, passes: [] }), { result: RESULT.OK, visit: 1 });
    // lewat ke-2 setelah ≥ 10 menit → TERCATAT (WS2)
    assert.deepEqual(classifyTiming({ ...base, passes: [now - PASS_GAP_MS] }), { result: RESULT.OK, visit: 2 });
    assert.deepEqual(classifyTiming({ ...base, passes: [now - 70 * 60_000] }), { result: RESULT.OK, visit: 2 });
    // < 10 menit → masih "sudah discan"
    assert.equal(classifyTiming({ ...base, passes: [now - PASS_GAP_MS + 1] }).result, RESULT.ALREADY);
    // sudah 2x → lewat ke-3 tidak ada di rute
    assert.equal(classifyTiming({ ...base, passes: [now - 80 * 60_000, now - 20 * 60_000] }).result, RESULT.ALREADY);
    // 7K lewat pos yang sama hanya 1x → scan 70 menit kemudian tetap "sudah discan"
    assert.equal(classifyTiming({ ...base, visits: 1, passes: [now - 70 * 60_000] }).result, RESULT.ALREADY);
});

test('clusterPasses: kasus sama dengan PHP (tests/Fixtures/pass-cluster-cases.json)', () => {
    const { cases, gap_ms } = JSON.parse(readFileSync(new URL('../Fixtures/pass-cluster-cases.json', import.meta.url), 'utf8'));
    assert.equal(gap_ms, PASS_GAP_MS);
    for (const c of cases) assert.deepEqual(clusterPasses(c.times), c.expected, c.name);
});

test('passTimes merges local scans and server passes', () => {
    const scan = (ms, extra = {}) => ({ device_time_ms: ms, clock_offset_ms: 0, cancelled: false, status: 'synced', reason: null, local_result: 'ok', ...extra });
    assert.deepEqual(passTimes([scan(8_400_000)], [4_200_000]), [4_200_000, 8_400_000]);
    assert.deepEqual(passTimes([scan(4_230_000), scan(8_400_000, { cancelled: true })], [4_200_000]), [4_200_000]);
});

test('previousPass uses earliest corrected time and skips cancelled / rejected', () => {
    const scan = (ms, offset, extra = {}) => ({ device_time_ms: ms, clock_offset_ms: offset, cancelled: false, status: 'synced', reason: null, local_result: 'ok', ...extra });

    assert.equal(previousPass([], null), null);
    assert.equal(previousPass([], 500), 500);
    assert.equal(previousPass([scan(1000, -200), scan(900, 500)], null), 800);
    assert.equal(previousPass([scan(1000, 0, { cancelled: true })], null), null);
    assert.equal(previousPass([scan(1000, 0, { status: 'rejected', reason: 'unknown_bib' })], null), null);
    assert.equal(previousPass([scan(1000, 0, { status: 'rejected', reason: 'duplicate' })], null), 1000);
    assert.equal(previousPass([scan(1000, 0, { local_result: 'wrong' })], null), null);
    assert.equal(previousPass([scan(1000, 0)], 700), 700); // device lain lebih awal
});

test('parseRacepackCode', () => {
    assert.deepEqual(parseRacepackCode('gtr202600013'), { type: 'registration', value: 'GTR202600013' });
    assert.deepEqual(parseRacepackCode(' 07001 '), { type: 'bib', value: '7001' });
    assert.equal(parseRacepackCode('abc'), null);
});

test('nextSyncDelay: 10s, then exponential backoff capped at 5 minutes', () => {
    assert.deepEqual([0, 1, 2, 3, 4, 5, 9].map((f) => nextSyncDelay(f)), [10_000, 20_000, 40_000, 80_000, 160_000, 300_000, 300_000]);
});

test('clock offset handles negative offset (device ahead of server)', () => {
    // device 5 s lebih cepat: kirim di 105_000 (device), server 100_100, terima di 105_200 (device)
    assert.equal(offsetFrom(100_100, 105_000, 105_200), -5_000);
    assert.equal(offsetFrom(100_100, 95_000, 95_200), 5_000);
});

test('feedbackKind distinguishes the four signals', () => {
    assert.deepEqual(
        [RESULT.OK, RESULT.ALREADY, RESULT.WRONG_CATEGORY, RESULT.UNKNOWN, RESULT.INVALID].map(feedbackKind),
        ['ok', 'already', 'wrong', 'unknown', 'unknown'],
    );
});

test('belongsTo: only the logged-in user sees / sends their queue items', () => {
    assert.equal(belongsTo({ user_id: 5 }, 5), true);
    assert.equal(belongsTo({ user_id: 5 }, 6), false);
    assert.equal(belongsTo({ user_id: 5 }, null), false);
    assert.equal(belongsTo({}, 6), true); // item lama tanpa user_id
});
