<script setup>
import { computed, ref } from 'vue';
import { adminApi } from '../../store.js';
import { formatTime } from '../../clock.js';
import Icon from '../Icon.vue';

/**
 * Satu scan + aksi admin: Abaikan (scan sah), Pulihkan (diabaikan & punya peserta),
 * Perbaiki BIB (salah ketik / tak terdaftar). Hasil kategori dihitung ulang di server.
 */
const props = defineProps({
    scan: { type: Object, required: true },
    showName: { type: Boolean, default: true },
});
const emit = defineEmits(['changed', 'notify', 'track']);
const busy = ref(false);

const SOURCE = { qr: 'QR', manual: 'Keypad', pulltag: 'Pull tag', admin: 'Koreksi admin' };
const canRestore = computed(() => props.scan.is_ignored && props.scan.bib && props.scan.reason !== 'duplicate');
// Scan sah pun bisa salah orang (marshal salah ketik ke BIB lain yang terdaftar) → boleh dipindah.
const canRelink = computed(() => !props.scan.is_ignored || ['unknown_bib', 'invalid_bib', 'wrong_category'].includes(props.scan.reason));

async function act(action) {
    let body;
    if (action === 'relink') {
        const bib = prompt(`Scan "${props.scan.bib_input}" di ${props.scan.point}. BIB yang benar?`);
        if (!bib?.trim()) return;
        body = { bib: bib.trim() };
    } else if (!confirm(action === 'ignore'
        ? `Abaikan scan BIB ${props.scan.bib ?? props.scan.bib_input} di ${props.scan.point}? Scan ini tidak dihitung di hasil.`
        : `Pulihkan scan BIB ${props.scan.bib} di ${props.scan.point}?`)) {
        return;
    }

    busy.value = true;
    try {
        const res = await adminApi(`/scans/${props.scan.id}/${action}`, { method: 'POST', body });
        emit('changed', res.scan);
        emit('notify', res.message);
    } catch (e) {
        emit('notify', e.message || 'Gagal');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <li class="scanrow" :class="{ ignored: scan.is_ignored, flagged: scan.is_flagged }">
        <span class="code">{{ scan.point ?? '—' }}</span>
        <button class="who" :disabled="!scan.bib" @click="emit('track', scan.bib)">
            <span>
                <b class="num">{{ scan.bib ?? scan.bib_input }}</b>
                <template v-if="showName && scan.name"> · {{ scan.name }}</template>
                <template v-else-if="!scan.bib"> (diketik "{{ scan.bib_input }}")</template>
            </span>
            <small>
                {{ formatTime(scan.scanned_at_ms) }} · {{ SOURCE[scan.source] ?? scan.source }}<template v-if="scan.scanner"> · {{ scan.scanner }}</template>
            </small>
            <small v-if="scan.reason_label || scan.is_flagged" class="why">
                <template v-if="scan.reason_label">{{ scan.reason_label }}</template>
                <template v-if="scan.is_flagged"> · jam HP janggal</template>
            </small>
        </button>
        <div class="acts">
            <button v-if="canRelink" class="btn sm" :disabled="busy" @click="act('relink')"><Icon name="link" :size="16" /> BIB</button>
            <button v-if="canRestore" class="btn sm" :disabled="busy" @click="act('restore')"><Icon name="undo" :size="16" /> Pulihkan</button>
            <button v-if="!scan.is_ignored" class="btn sm danger" :disabled="busy" @click="act('ignore')"><Icon name="x" :size="16" /> Abaikan</button>
        </div>
    </li>
</template>
