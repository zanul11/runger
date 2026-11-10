<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from './Icon.vue';

/** Kamera QR (qr-scanner, dimuat hanya saat dipakai). Kode sama dalam 3 detik diabaikan. */
const props = defineProps({ hint: { type: String, default: 'Arahkan QR ke dalam bingkai' } });
const emit = defineEmits(['decode']);
const video = ref(null);
const error = ref(null);
let scanner = null;
let last = { text: null, at: 0 };

onMounted(async () => {
    try {
        const { default: QrScanner } = await import('qr-scanner');
        scanner = new QrScanner(video.value, (res) => {
            const text = (res.data ?? '').trim();
            const now = Date.now();
            if (!text || (text === last.text && now - last.at < 3000)) return;
            last = { text, at: now };
            emit('decode', text);
        }, {
            preferredCamera: 'environment',
            maxScansPerSecond: 6,
            returnDetailedScanResult: true,
        });
        await scanner.start();
    } catch {
        error.value = location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1'
            ? 'Kamera butuh HTTPS.'
            : 'Kamera tidak bisa dibuka. Izinkan akses kamera, atau pakai keypad.';
    }
});

onBeforeUnmount(() => {
    scanner?.stop();
    scanner?.destroy();
});
</script>

<template>
    <div class="qr">
        <video ref="video" playsinline muted></video>
        <div class="qr-frame"></div>
        <p class="qr-hint">{{ props.hint }}</p>
        <div v-if="error" class="alert bad"><Icon name="x-circle" :size="20" /><span>{{ error }}</span></div>
    </div>
</template>
