/**
 * Umpan balik getar + suara, berbeda per hasil supaya marshal tidak perlu melihat layar:
 *  ok       : 1 bip tinggi pendek
 *  already  : 2 bip sedang          (sudah discan)
 *  wrong    : dengung rendah putus  (salah kategori / pos)
 *  unknown  : nada turun panjang    (BIB tidak terdaftar / tidak valid)
 * Suara dibuat dengan WebAudio (tanpa file) supaya tetap jalan offline.
 */
const PATTERNS = {
    ok: { vibrate: [90], tones: [[1320, 0.12, 'sine']] },
    already: { vibrate: [70, 70, 70], tones: [[880, 0.1, 'sine'], [0, 0.07], [880, 0.1, 'sine']] },
    wrong: { vibrate: [300, 120, 300], tones: [[220, 0.25, 'square'], [0, 0.1], [220, 0.25, 'square']] },
    unknown: { vibrate: [700], tones: [[600, 0.2, 'sawtooth'], [400, 0.2, 'sawtooth'], [250, 0.35, 'sawtooth']] },
};

let ctx = null;

/** Panggil dari aksi pengguna (tap/klik) — browser butuh gesture untuk mengaktifkan audio. */
export function unlockAudio() {
    try {
        ctx ??= new (window.AudioContext || window.webkitAudioContext)();
        if (ctx.state === 'suspended') ctx.resume();
    } catch {
        ctx = null;
    }
}

export function feedback(kind) {
    const p = PATTERNS[kind] ?? PATTERNS.unknown;

    try {
        navigator.vibrate?.(p.vibrate);
    } catch {
        /* iOS tidak punya vibrate */
    }

    if (!ctx) return;
    let t = ctx.currentTime;
    for (const [freq, dur, type] of p.tones) {
        if (freq > 0) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, t);
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(0.6, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + dur);
            osc.connect(gain).connect(ctx.destination);
            osc.start(t);
            osc.stop(t + dur + 0.02);
        }
        t += dur;
    }
}
