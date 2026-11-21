import { createApp, h } from 'vue';
import RosterBoard from './RosterBoard.vue';

/**
 * Halaman publik /crew/roster — roster panitia BACA-SAJA (rapat / proyektor), tanpa login.
 * No. HP disamarkan server. Mengisi petugas: GTR Crew (/crew → Roster).
 */
async function load() {
    const res = await fetch('/crew/roster/data', { headers: { Accept: 'application/json' }, cache: 'no-store' });
    const body = await res.json().catch(() => null);
    if (!res.ok) throw new Error(body?.message || `HTTP ${res.status}`);
    return body;
}

createApp({ render: () => h(RosterBoard, { load, pollMs: 10000 }) }).mount('#roster');
