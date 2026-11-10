import { reactive } from 'vue';
import { api, ApiError } from './api.js';
import { db, deviceId, getMeta, setMeta, deleteMeta, replaceParticipants } from './db.js';
import { loadClock, syncClock, clock } from './clock.js';
import { normalizeBib } from './bib.js';
import { classifyTiming, passTimes, parseRacepackCode, belongsTo, PASS_GAP_MS, RESULT } from './logic.js';

/** State global scanner (reaktif untuk komponen Vue). */
export const state = reactive({
    ready: false,
    session: null,        // { token, device_id, user, timing_point, categories, rules }
    rosterCount: 0,
    rosterAt: null,
    online: navigator.onLine,
    pending: 0,
    syncing: false,
    lastSyncAt: null,
    lastError: null,
    authExpired: false,
    clock,
});

/** ID user yang sedang login (null bila belum login). */
export function currentUserId() {
    return state.session?.user?.id ?? null;
}

/** Filter Dexie: hanya item milik user yang sedang login. */
export const mine = (item) => belongsTo(item, currentUserId());

export async function refreshCounts() {
    // Antrean yang ditampilkan & dikirim hanya milik user yang sedang login.
    const [scans, racepack, roster] = await Promise.all([
        db.scans.where('status').equals('pending').filter(mine).count(),
        db.racepack.where('status').equals('pending').filter(mine).count(),
        db.participants.count(),
    ]);
    state.pending = scans + racepack;
    state.rosterCount = roster;
}

/** Saat app dibuka: pulihkan sesi dari IndexedDB (tanpa jaringan). */
export async function restore() {
    state.session = await getMeta('session');
    state.rosterAt = await getMeta('roster_at');
    await loadClock();
    await refreshCounts();
    state.ready = true;
}

async function applySession(payload, token) {
    const session = {
        token,
        device_id: payload.device_id,
        user: payload.user,
        timing_point: payload.timing_point,
        categories: payload.categories,
        rules: payload.rules,
    };
    await replaceParticipants(payload.participants);
    await setMeta('session', session);
    await setMeta('roster_at', Date.now());
    state.session = session;
    state.rosterAt = Date.now();
    state.authExpired = false;
    await refreshCounts();
}

export async function login(username, password) {
    const payload = await api('/session', {
        method: 'POST',
        body: { username, password, device_id: await deviceId() },
    });
    await applySession(payload, payload.token);
    await syncClock(payload.token).catch(() => {});
}

/** Muat ulang roster & pos dari server (pos bisa berubah bila marshal dipindah). */
export async function refreshRoster() {
    if (!state.session?.token) return;
    try {
        const payload = await api('/session', { token: state.session.token, timeout: 20000 });
        await applySession(payload, state.session.token);
    } catch (e) {
        if (e instanceof ApiError && e.status === 401) state.authExpired = true;
        throw e;
    }
}

export async function logout() {
    const token = state.session?.token;
    if (token && navigator.onLine) {
        await api('/session', { method: 'DELETE', token, timeout: 5000 }).catch(() => {});
    }
    await deleteMeta('session');
    state.session = null;
}

export function pointCategoryIds() {
    return (state.session?.categories ?? []).filter((c) => c.passes_point).map((c) => c.id);
}

/** Kunjungan kategori di pos marshal: [{code, km}] — 15K di Pertigaan = [WS1, WS2]. */
export function visitsFor(categoryId) {
    return state.session?.categories?.find((c) => c.id === categoryId)?.visits ?? [];
}

export function categoryName(id) {
    return state.session?.categories?.find((c) => c.id === id)?.name ?? '—';
}

/**
 * Catat input timing (QR / keypad). Selalu disimpan ke antrean lokal dulu (juga yang salah,
 * untuk audit server), lalu dikirim oleh sync.js.
 */
export async function recordScan(raw, source) {
    const point = state.session.timing_point;
    const bib = normalizeBib(raw);
    const participant = bib ? await db.participants.get(bib) : null;

    const local = bib ? await db.scans.where('[timing_point_id+bib]').equals([point.id, bib]).toArray() : [];
    const gapMs = state.session.rules?.repeat_pass_gap_ms ?? PASS_GAP_MS;
    const passes = passTimes(local, participant?.seen_passes_ms ?? (participant?.seen_at_ms != null ? [participant.seen_at_ms] : []), gapMs);
    const visitList = visitsFor(participant?.category_id);

    const { result, visit } = classifyTiming({
        bib, participant, pointCategoryIds: pointCategoryIds(),
        passes, visits: visitList.length || 1, nowMs: Date.now() + (clock.offsetMs || 0), gapMs,
    });

    const scan = {
        uuid: crypto.randomUUID(),
        bib: bib ?? String(raw).slice(0, 32),
        bib_input: String(raw),
        timing_point_id: point.id,
        device_time_ms: Date.now(),
        clock_offset_ms: clock.offsetMs || 0,
        source,
        cancelled: false,
        status: 'pending',
        reason: null,
        local_result: result,
        name: participant?.name ?? null,
        category_id: participant?.category_id ?? null,
        gender: participant?.gender ?? null,
        created_at: Date.now(),
        user_id: currentUserId(),
    };
    await db.scans.add(scan);
    await refreshCounts();

    return { scan, result, participant, passes, visit, visitList };
}

/** "Batalkan": tandai dibatalkan (tidak dihapus) lalu kirim ulang uuid yang sama ke server. */
export async function cancelScan(uuid) {
    await db.scans.update(uuid, { cancelled: true, status: 'pending' });
    await refreshCounts();
}

/** Riwayat scan terakhir milik user yang sedang login (scan marshal lain di HP ini tidak tampil). */
export function recentScans(limit = 8) {
    return db.scans.orderBy('created_at').reverse().filter(mine).limit(limit).toArray();
}

// ---------------------------------------------------------------- Peran ----

/** marshal = timing · racepack = pengambilan racepack · admin = keduanya. */
export function canTiming() {
    return ['marshal', 'admin'].includes(state.session?.user?.role);
}

export function canRacepack() {
    return ['racepack', 'admin'].includes(state.session?.user?.role);
}

// ------------------------------------------------------------- Racepack ----

/** Peserta dari roster lokal → bentuk yang sama dengan jawaban server. */
function fromRoster(p) {
    return p && {
        bib: p.bib,
        registration_number: p.registration_number,
        name: p.name,
        category: categoryName(p.category_id),
        category_id: p.category_id,
        gender: p.gender,
        size: p.size,
        nik: p.nik ?? null,
        club: null,
        racepack_collected_at_ms: null,
        racepack_collected_by: null,
        racepack_picked_by: null,
    };
}

async function findLocal(code) {
    return code.type === 'bib'
        ? db.participants.get(code.value)
        : db.participants.where('registration_number').equals(code.value).first();
}

/**
 * Langkah 1 — cari peserta & status racepack (tidak mengubah data).
 * Online → jawaban resmi server. Offline / gagal jaringan → roster lokal.
 *
 * @returns {{status: 'available'|'already'|'not_paid'|'not_found'|'invalid', participant: object|null, code: string|null, offline: boolean}}
 */
export async function racepackLookup(raw) {
    const code = parseRacepackCode(raw);
    if (!code) return { status: 'invalid', participant: null, code: null, offline: false };

    if (navigator.onLine && state.session?.token) {
        try {
            const res = await api(`/racepack/lookup?code=${encodeURIComponent(code.value)}`, { token: state.session.token, timeout: 6000 });
            return { status: res.status, participant: res.participant, code: code.value, offline: false };
        } catch (e) {
            if (e instanceof ApiError && !e.retryable) {
                return { status: e.body?.status ?? 'not_found', participant: e.body?.participant ?? null, code: code.value, offline: false };
            }
            // jaringan gagal → pakai roster lokal
        }
    }

    const local = await findLocal(code);
    if (!local) return { status: 'not_found', participant: null, code: code.value, offline: true };

    return { status: local.racepack ? 'already' : 'available', participant: fromRoster(local), code: code.value, offline: true };
}

/**
 * Langkah 2 — serahkan racepack (setelah petugas melihat detail & menekan "Ambil").
 * Online → langsung ke server. Offline / gagal jaringan → masuk antrean, dikirim sync.js.
 *
 * @returns {{status: string, participant: object|null, queued: boolean}}
 */
export async function racepackCheckin(code, pickedBy = null) {
    const parsed = parseRacepackCode(code);
    const local = parsed ? await findLocal(parsed) : null;
    const body = { code, picked_by: pickedBy || null, collected_at_ms: Date.now() + (clock.offsetMs || 0) };

    if (navigator.onLine && state.session?.token) {
        try {
            const res = await api('/racepack/checkin', { method: 'POST', token: state.session.token, timeout: 8000, body });
            if (local) await db.participants.update(local.bib, { racepack: true });
            return { status: res.status, participant: res.participant, queued: false };
        } catch (e) {
            if (e instanceof ApiError && !e.retryable) {
                return { status: e.body?.status ?? 'error', participant: e.body?.participant ?? null, queued: false };
            }
        }
    }

    await db.racepack.add({
        uuid: crypto.randomUUID(),
        code,
        picked_by: body.picked_by,
        collected_at_ms: body.collected_at_ms,
        status: 'pending',
        created_at: Date.now(),
        user_id: currentUserId(),
        name: local?.name ?? null,
    });
    if (local) await db.participants.update(local.bib, { racepack: true });
    await refreshCounts();

    return { status: 'ok', participant: fromRoster(local), queued: true };
}

/**
 * Data peserta di HP untuk layar "Data Peserta": roster + waktu lewat pos ini (gabungan scan
 * lokal semua user di HP ini dan `seen_at_ms` dari server).
 */
export async function participantOverview() {
    const list = await db.participants.toArray();
    const point = state.session?.timing_point;
    const passed = new Map();

    if (point) {
        const scans = await db.scans.filter((s) => s.timing_point_id === point.id).toArray();
        const byBib = new Map();
        for (const s of scans) {
            if (!byBib.has(s.bib)) byBib.set(s.bib, []);
            byBib.get(s.bib).push(s);
        }
        const gapMs = state.session.rules?.repeat_pass_gap_ms ?? PASS_GAP_MS;
        for (const p of list) {
            const server = p.seen_passes_ms ?? (p.seen_at_ms != null ? [p.seen_at_ms] : []);
            const ps = passTimes(byBib.get(p.bib) ?? [], server, gapMs);
            if (ps.length) passed.set(p.bib, ps);
        }
    }

    return list
        .map((p) => ({ ...p, passes_ms: passed.get(p.bib) ?? [], passed_at_ms: passed.get(p.bib)?.[0] ?? null }))
        .sort((a, b) => Number(a.bib) - Number(b.bib));
}

/** Semua peserta di HP (untuk pencarian nama di meja racepack). */
export function rosterList() {
    return db.participants.toArray();
}

export { RESULT };
