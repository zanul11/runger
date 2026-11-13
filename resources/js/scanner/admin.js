import { onBeforeUnmount, onMounted, ref } from 'vue';
import { state } from './store.js';
import { nowMs } from './clock.js';

/** Durasi ms → "1:02:03" (atau "02:03" di bawah 1 jam). */
export function fmtDur(ms, withMs = false) {
    if (ms == null) return '—';
    const neg = ms < 0;
    let s = Math.floor(Math.abs(ms) / 1000);
    const h = Math.floor(s / 3600);
    s -= h * 3600;
    const m = Math.floor(s / 60);
    s -= m * 60;
    const pad = (n) => String(n).padStart(2, '0');
    const base = h ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    const frac = withMs ? '.' + String(Math.abs(ms) % 1000).padStart(3, '0') : '';
    return (neg ? '−' : '') + base + frac;
}

/** "baru saja" / "5 mnt lalu" / "2 jam lalu". */
export function ago(ms, now = nowMs()) {
    if (ms == null) return '—';
    const min = Math.floor((now - ms) / 60_000);
    if (min < 1) return 'baru saja';
    if (min < 60) return `${min} mnt lalu`;
    return `${Math.floor(min / 60)} jam ${min % 60} mnt lalu`;
}

/** No. HP lokal (08…/62…) → link wa.me. */
export function waLink(phone) {
    const d = String(phone ?? '').replace(/\D/g, '').replace(/^0/, '62');
    return d ? `https://wa.me/${d}` : null;
}

export const STATUS = {
    on_course: { label: 'Di lintasan', cls: 'part' },
    finished: { label: 'Finish', cls: 'yes' },
    not_started: { label: 'Belum terlihat', cls: 'todo' },
    dnf: { label: 'DNF', cls: 'bad' },
    dq: { label: 'DQ', cls: 'bad' },
    dns: { label: 'DNS', cls: 'todo' },
    finisher: { label: 'Finisher', cls: 'yes' },
};

/**
 * Muat data dari server lalu ulangi tiap `everyMs` selama layar terbuka, online & app di depan.
 * @returns {{ data, error, loading, load }}
 */
export function usePoll(fetcher, everyMs = 20_000) {
    const data = ref(null);
    const error = ref(null);
    const loading = ref(false);
    let timer;

    async function load() {
        if (loading.value) return;
        loading.value = true;
        try {
            data.value = await fetcher();
            error.value = null;
        } catch (e) {
            error.value = state.online ? (e.message || 'Gagal memuat') : 'Offline — menampilkan data terakhir';
        } finally {
            loading.value = false;
        }
    }

    function loop() {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            if (document.visibilityState === 'visible' && state.online) await load();
            loop();
        }, everyMs);
    }

    const onVisible = () => { if (document.visibilityState === 'visible' && state.online) load(); };

    onMounted(() => {
        load();
        loop();
        document.addEventListener('visibilitychange', onVisible);
    });
    onBeforeUnmount(() => {
        clearTimeout(timer);
        document.removeEventListener('visibilitychange', onVisible);
    });

    return { data, error, loading, load };
}
