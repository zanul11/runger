<script setup>
import { ref } from 'vue';
import { state, chooseAdminLocation } from '../../store.js';
import Icon from '../Icon.vue';

/** Admin wajib memilih lokasi sebelum scan: meja Race Pack atau salah satu pos timing. */
const emit = defineEmits(['notify']);
const busy = ref(null);

async function pick(loc, label) {
    busy.value = loc;
    try {
        await chooseAdminLocation(loc);
        emit('notify', `Lokasi scan: ${label}`);
    } catch (e) {
        emit('notify', e.message || 'Gagal memilih lokasi');
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <main class="panel admin">
        <div class="alert warn"><Icon name="pin" :size="20" /><span>Pilih <b>lokasi Anda sekarang</b> sebelum scan. Scan tercatat di lokasi yang dipilih.</span></div>

        <div class="section-title"><span>Pengambilan</span></div>
        <button class="locpick rp" :disabled="!!busy" @click="pick('racepack', 'Race Pack')">
            <span class="loc-badge"><Icon name="package" :size="22" /></span>
            <span class="grow"><b>Meja Race Pack</b><small>Scan QR e-ticket / BIB saat racepack diambil</small></span>
            <Icon name="chevron-right" :size="20" />
        </button>

        <div class="section-title"><span>Pos timing (scan BIB)</span></div>
        <button v-for="p in state.session.points ?? []" :key="p.id" class="locpick" :disabled="!!busy" @click="pick(p.id, `${p.code} · ${p.name}`)">
            <span class="loc-badge">{{ p.code }}</span>
            <span class="grow">
                <b>{{ p.name }}</b>
                <small v-if="state.session.timing_point?.id === p.id">Pos terakhir Anda · bisa dipilih tanpa sinyal</small>
                <small v-else>{{ busy === p.id ? 'Memuat data pos…' : 'Butuh sinyal untuk pindah pos' }}</small>
            </span>
            <Icon name="chevron-right" :size="20" />
        </button>
        <p v-if="!(state.session.points ?? []).length" class="empty">Daftar pos belum ada di HP ini — buka menu lalu "Unduh ulang data" selagi ada sinyal.</p>
    </main>
</template>
