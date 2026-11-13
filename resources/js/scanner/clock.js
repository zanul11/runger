import { api } from './api.js';
import { getMeta, setMeta } from './db.js';

/**
 * Koreksi jam device terhadap server.
 * offset = jam_server − jam_device (ms). Scan menyimpan jam device mentah + offset saat itu;
 * server menghitung waktu terkoreksi = device_time_ms + clock_offset_ms.
 */
export const clock = { offsetMs: 0, rttMs: null, syncedAt: null };

export async function loadClock() {
    Object.assign(clock, await getMeta('clock', {}));
}

/** Hitung offset dari server_time_ms + perkiraan setengah RTT. */
export function offsetFrom(serverMs, sentAt, receivedAt) {
    return Math.round(serverMs + (receivedAt - sentAt) / 2 - receivedAt);
}

/** Ambil 3 sampel /time, pakai yang RTT-nya paling kecil (paling akurat). */
export async function syncClock(token) {
    let best = null;
    for (let i = 0; i < 3; i++) {
        const sentAt = Date.now();
        const res = await api('/time', { token, timeout: 8000 });
        const receivedAt = Date.now();
        const rtt = receivedAt - sentAt;
        if (!best || rtt < best.rttMs) {
            best = { offsetMs: offsetFrom(res.server_time_ms, sentAt, receivedAt), rttMs: rtt };
        }
    }
    // RTT > 5 detik terlalu tidak akurat; pertahankan offset lama.
    if (best.rttMs > 5000 && clock.syncedAt) return clock;

    Object.assign(clock, best, { syncedAt: Date.now() });
    await setMeta('clock', { ...clock });

    return clock;
}

/** Waktu terkoreksi sekarang (ms). */
export function nowMs() {
    return Date.now() + (clock.offsetMs || 0);
}

const fmt = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
});

const fmtDay = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Makassar', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false,
});

/** "27 Nov 09.12" → "27 Nov 09:12" dalam WITA (untuk kejadian di hari lain, mis. ambil racepack). */
export function formatDayTime(ms) {
    return ms == null ? '—' : fmtDay.format(new Date(ms)).replace(/(\d{2})\.(\d{2})$/, '$1:$2').replace(',', '');
}

/** "07:10:05" dalam WITA. */
export function formatTime(ms) {
    return ms == null ? '—' : fmt.format(new Date(ms)).replace(/\./g, ':');
}
