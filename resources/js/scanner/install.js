import { reactive } from 'vue';

/**
 * Pemasangan PWA ("Tambahkan ke layar utama").
 *  - Chrome/Edge/Samsung (Android & desktop): browser mengirim `beforeinstallprompt`; kita simpan
 *    event-nya supaya tombol "Pasang" di aplikasi bisa memunculkan dialog pasang kapan saja.
 *  - iOS/iPadOS: tidak ada dialog otomatis — tampilkan langkah manual (Bagikan → Tambah ke Layar Utama).
 * Modul ini diimpor paling awal di main.js supaya event tidak terlewat.
 */
const DISMISS_KEY = 'gtr-install-dismissed';

function isStandalone() {
    return window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true;
}
function isIos() {
    const ua = navigator.userAgent;
    // iPadOS 13+ mengaku "Macintosh" tapi punya layar sentuh.
    return /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
}
function readDismissed() {
    try { return localStorage.getItem(DISMISS_KEY) === '1'; } catch { return false; }
}

export const install = reactive({
    installed: isStandalone(),
    canPrompt: false,       // dialog pasang browser siap dipanggil
    ios: isIos(),
    dismissed: readDismissed(),
});

let deferred = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault(); // tahan mini-infobar; dialog dipicu dari tombol kita
    deferred = e;
    install.canPrompt = true;
});
window.addEventListener('appinstalled', () => {
    deferred = null;
    install.canPrompt = false;
    install.installed = true;
});
window.matchMedia?.('(display-mode: standalone)').addEventListener?.('change', (e) => {
    if (e.matches) install.installed = true;
});

/** Munculkan dialog pasang browser. Mengembalikan true bila pengguna menyetujui. */
export async function promptInstall() {
    if (!deferred) return false;
    const event = deferred;
    deferred = null;
    install.canPrompt = false; // satu event hanya bisa dipakai sekali
    event.prompt();
    const { outcome } = await event.userChoice;
    return outcome === 'accepted';
}

export function dismissInstall() {
    install.dismissed = true;
    try { localStorage.setItem(DISMISS_KEY, '1'); } catch { /* abaikan */ }
}
