import { api, ApiError } from './api.js';
import { db } from './db.js';
import { syncClock } from './clock.js';
import { nextSyncDelay } from './logic.js';
import { state, refreshCounts, refreshRoster, mine, pushPendingStart } from './store.js';

/**
 * Sinkronisasi antrean ke server:
 *  - tiap 10 detik bila online; gagal → tunda eksponensial (20s, 40s, … maks 5 menit)
 *  - langsung saat kembali online / app kembali ke depan / ada scan baru
 *  - jam di-resync setiap kali online kembali (dan tiap 10 menit)
 *  - roster di-refresh tiap 5 menit (melihat scan dari device lain & pindah pos)
 * Item hanya berubah status setelah server menjawab; error jaringan = coba lagi nanti.
 */
const BATCH = 200;
const CLOCK_EVERY = 10 * 60_000;
const ROSTER_EVERY = 5 * 60_000;

let timer = null;
let failures = 0;
let running = false;
let lastClockAt = 0;

function schedule(ms) {
    clearTimeout(timer);
    timer = setTimeout(tick, ms);
}

/** Minta sync secepatnya (mis. setelah scan baru). */
export function syncSoon(ms = 300) {
    if (!running) schedule(ms);
}

export function startSync() {
    window.addEventListener('online', () => {
        state.online = true;
        lastClockAt = 0; // resync jam saat online
        failures = 0;
        syncSoon(0);
    });
    window.addEventListener('offline', () => {
        state.online = false;
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') syncSoon(0);
    });
    schedule(0);
}

async function tick() {
    running = true;
    try {
        state.online = navigator.onLine;
        const token = state.session?.token;
        if (!state.online || !token || state.authExpired) return;

        state.syncing = true;

        if (Date.now() - lastClockAt > CLOCK_EVERY) {
            await syncClock(token);
            lastClockAt = Date.now();
        }

        await pushPendingStart();
        await pushScans(token);
        await pushRacepack(token);

        if (!state.rosterAt || Date.now() - state.rosterAt > ROSTER_EVERY) {
            await refreshRoster();
        }

        failures = 0;
        state.lastError = null;
        state.lastSyncAt = Date.now();
    } catch (e) {
        if (e instanceof ApiError && e.status === 401) {
            state.authExpired = true;
            state.lastError = 'Sesi habis — login ulang (antrean tetap tersimpan).';
        } else {
            failures++;
            state.lastError = e.message || 'Gagal sinkron';
        }
    } finally {
        state.syncing = false;
        running = false;
        await refreshCounts();
        schedule(nextSyncDelay(failures));
    }
}

async function pushScans(token) {
    for (;;) {
        // Hanya scan milik user yang login: dikirim dengan token user ini → scanned_by benar.
        const items = await db.scans.where('status').equals('pending').filter(mine).limit(BATCH).toArray();
        if (!items.length) return;

        const res = await api('/scans/batch', {
            method: 'POST',
            token,
            timeout: 30000,
            body: {
                scans: items.map((s) => ({
                    uuid: s.uuid,
                    bib: s.bib_input,
                    timing_point_id: s.timing_point_id,
                    device_time_ms: s.device_time_ms,
                    clock_offset_ms: s.clock_offset_ms,
                    source: s.source,
                    cancelled: s.cancelled,
                })),
            },
        });

        const rejected = new Map(res.rejected.map((r) => [r.uuid, r.reason]));
        await db.transaction('rw', db.scans, async () => {
            for (const s of items) {
                // Bila dibatalkan saat request berjalan, biarkan pending → terkirim lagi.
                const current = await db.scans.get(s.uuid);
                if (current && current.cancelled !== s.cancelled) continue;

                if (res.accepted.includes(s.uuid)) {
                    await db.scans.update(s.uuid, { status: 'synced', reason: null });
                } else if (rejected.has(s.uuid)) {
                    await db.scans.update(s.uuid, { status: 'rejected', reason: rejected.get(s.uuid) });
                }
            }
        });

        if (items.length < BATCH) return;
    }
}

async function pushRacepack(token) {
    const items = await db.racepack.where('status').equals('pending').filter(mine).toArray();
    for (const item of items) {
        try {
            const res = await api('/racepack/checkin', {
                method: 'POST', token, timeout: 15000,
                body: { code: item.code, picked_by: item.picked_by ?? null, collected_at_ms: item.collected_at_ms },
            });
            await db.racepack.update(item.uuid, { status: 'synced', reason: res.status });
        } catch (e) {
            if (e instanceof ApiError && !e.retryable && e.status !== 401) {
                await db.racepack.update(item.uuid, { status: 'rejected', reason: e.body?.status ?? `http_${e.status}` });
            } else {
                throw e;
            }
        }
    }
}
