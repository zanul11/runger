<script setup>
import { adminApi } from '../../store.js';
import { formatTime } from '../../clock.js';
import { ago, usePoll, waLink } from '../../admin.js';
import Icon from '../Icon.vue';

/**
 * Sweep: peserta yang sudah tercatat di lintasan tapi belum lewat CP terakhir & belum finish.
 * Paling lama tak terlihat di atas. Ketuk baris → lacak; tombol telepon / WA kontak darurat.
 */
const emit = defineEmits(['back', 'track']);
const { data, error, loading, load } = usePoll(() => adminApi('/sweep'), 20_000);
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Sweep</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-for="c in data?.categories ?? []" :key="c.category">
            <section class="acard">
                <div class="acard-head">
                    <span class="code">{{ c.category }}</span>
                    <div class="grow">
                        <b>Belum lewat {{ c.last_cp ?? 'CP terakhir' }}</b>
                        <small>Di lintasan {{ c.on_course }} · finish {{ c.finished }} · belum terlihat {{ c.not_started }}<template v-if="c.withdrawn"> · mundur/DQ {{ c.withdrawn }}</template></small>
                    </div>
                    <b class="big num" :class="{ hot: c.on_course }">{{ c.on_course }}</b>
                </div>
            </section>
            <ul v-if="c.rows.length" class="alist">
                <li v-for="r in c.rows" :key="r.bib" class="tap" @click="emit('track', r.bib)">
                    <b class="bib num">{{ r.bib }}</b>
                    <div class="grow">
                        <b>{{ r.name }}</b>
                        <small>Terakhir <b>{{ r.last_point }}</b> {{ formatTime(r.last_at_ms) }} · {{ ago(r.last_at_ms) }}</small>
                    </div>
                    <a v-if="r.phone" class="round" :href="`tel:${r.phone}`" aria-label="Telepon peserta" @click.stop><Icon name="phone" :size="18" /></a>
                    <a v-if="r.emergency" class="round sos" :href="waLink(r.emergency)" target="_blank" rel="noopener" aria-label="WhatsApp kontak darurat" @click.stop><Icon name="message" :size="18" /></a>
                </li>
            </ul>
        </template>
        <p v-if="data && !data.categories.some((c) => c.rows.length)" class="empty">Tidak ada peserta yang tertinggal di lintasan.</p>
        <p class="tip"><Icon name="alert" :size="18" /><span>"Belum terlihat" = belum ada scan sama sekali (bisa DNS atau belum lewat pos pertama). Peserta yang ditetapkan DNF/DQ/DNS lewat <b>Lacak → Ubah status</b> tidak dicari lagi. Tombol <b>WA</b> menghubungi kontak darurat.</span></p>
    </main>
</template>
