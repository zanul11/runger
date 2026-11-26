<script setup>
import { computed, ref } from 'vue';
import { racepackHistory } from '../store.js';
import { formatDayTime } from '../clock.js';
import { usePoll } from '../admin.js';
import Icon from './Icon.vue';

/** Riwayat pengambilan race pack: waktu, petugas, dan wakil bila diwakilkan. */
const emit = defineEmits(['back']);
const { data, error, loading, load } = usePoll(racepackHistory, 30_000);

const q = ref('');
const onlyProxy = ref(false);
const fold = (s) => String(s ?? '').toLowerCase();
const items = computed(() => (data.value?.items ?? []).filter((r) => (!onlyProxy.value || r.picked_by)
    && (!q.value.trim() || [r.name, r.bib, r.registration_number, r.picked_by, r.collected_by].some((v) => fold(v).includes(fold(q.value.trim()))))));
const proxies = computed(() => (data.value?.items ?? []).filter((r) => r.picked_by).length);
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Riwayat Race Pack</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <div class="stats three">
                <div class="stat dark"><small>Diambil</small><b class="num">{{ data.collected }}/{{ data.total }}</b></div>
                <div class="stat"><small>Diwakilkan</small><b class="num">{{ proxies }}</b></div>
                <div class="stat"><small>Belum</small><b class="num">{{ data.total - data.collected }}</b></div>
            </div>

            <div class="input search">
                <Icon name="search" :size="20" />
                <input v-model="q" type="search" autocomplete="off" placeholder="Nama / BIB / wakil / petugas" aria-label="Cari riwayat">
                <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
            </div>
            <div class="chips">
                <button :class="{ on: !onlyProxy }" @click="onlyProxy = false">Semua</button>
                <button :class="{ on: onlyProxy }" @click="onlyProxy = true">Diwakilkan <b class="num">{{ proxies }}</b></button>
            </div>

            <ul v-if="items.length" class="alist">
                <li v-for="r in items" :key="r.registration_number">
                    <b class="bib num">{{ r.bib ?? '—' }}</b>
                    <div class="grow">
                        <b>{{ r.name }}</b>
                        <small>{{ r.category }}<template v-if="r.size"> · Jersey {{ r.size }}</template> · petugas {{ r.collected_by ?? '—' }}</small>
                        <small v-if="r.picked_by" class="hot"><Icon name="user" :size="14" /> Diwakilkan: {{ r.picked_by }}</small>
                        <small v-else>Diambil sendiri</small>
                    </div>
                    <span class="when"><b class="num">{{ formatDayTime(r.collected_at_ms) }}</b><small>WITA</small></span>
                </li>
            </ul>
            <p v-else class="empty">{{ data.items.length ? 'Tidak ada yang cocok.' : 'Belum ada race pack yang diserahkan.' }}</p>
        </template>
    </main>
</template>
