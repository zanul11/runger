import Dexie from 'dexie';

/**
 * IndexedDB scanner (tetap ada setelah HP restart / tanpa sinyal).
 *
 *  participants : roster dari server (bib kanonik sebagai kunci)
 *  scans        : antrean scan timing. status: pending | synced | rejected
 *  racepack     : antrean check-in racepack. status: pending | synced | rejected
 *  meta         : session (token, pos, kategori), clock (offset), device_id
 */
export const db = new Dexie('gtr-scanner');

db.version(1).stores({
    participants: 'bib, registration_number, category_id',
    scans: 'uuid, status, created_at, [timing_point_id+bib]',
    racepack: 'uuid, status, created_at',
    meta: 'key',
});

export async function getMeta(key, fallback = null) {
    const row = await db.meta.get(key);
    return row ? row.value : fallback;
}

export function setMeta(key, value) {
    return db.meta.put({ key, value });
}

export function deleteMeta(key) {
    return db.meta.delete(key);
}

/** ID acak stabil per instalasi (dipakai sebagai nama token di server). */
export async function deviceId() {
    let id = await getMeta('device_id');
    if (!id) {
        id = crypto.randomUUID();
        await setMeta('device_id', id);
    }
    return id;
}

/** Ganti roster lokal dengan data terbaru dari server. */
export async function replaceParticipants(list) {
    await db.transaction('rw', db.participants, async () => {
        await db.participants.clear();
        await db.participants.bulkPut(list);
    });
}
