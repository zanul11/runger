import './install.js'; // paling awal: menangkap event pasang PWA sebelum aplikasi dimuat
import { createApp } from 'vue';
import App from './App.vue';
import './scanner.css';
import { restore } from './store.js';
import { startSync } from './sync.js';

// Service worker scanner (scope /scanner/ saja) — precache aset supaya bisa dibuka tanpa sinyal.
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    navigator.serviceWorker.register('/scanner/sw.js', { scope: '/scanner/' }).catch((e) => {
        console.warn('Service worker gagal didaftarkan', e);
    });
}

// Minta penyimpanan persisten supaya antrean scan tidak dihapus browser saat memori penuh.
navigator.storage?.persist?.().catch(() => {});

restore().then(() => {
    createApp(App).mount('#app');
    startSync();
});
