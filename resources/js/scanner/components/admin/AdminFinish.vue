<script setup>
import { computed, ref, watch } from 'vue';
import { adminApi } from '../../store.js';
import { formatTime } from '../../clock.js';
import { fmtDur, usePoll } from '../../admin.js';
import Icon from '../Icon.vue';

/** Finish board: podium (top 3 per gender, urut gun time) & 10 finisher terbaru per kategori. */
const emit = defineEmits(['back', 'track']);
const { data, error, loading, load } = usePoll(() => adminApi('/finish'), 20_000);

const catId = ref(null);
watch(data, (d) => {
    if (d && !d.categories.some((c) => c.id === catId.value)) catId.value = d.categories[0]?.id ?? null;
});
const cat = computed(() => data.value?.categories.find((c) => c.id === catId.value));
const MEDAL = ['1', '2', '3'];
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Finish Board</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <div class="chips">
                <button v-for="c in data.categories" :key="c.id" :class="{ on: c.id === catId }" @click="catId = c.id">{{ c.name }} <b class="num">{{ c.finishers }}/{{ c.eligible }}</b></button>
            </div>
            <p v-if="!data.results_published" class="alert warn"><Icon name="lock" :size="18" /><span>Sementara — belum dipublikasikan. Cek "Perlu review" sebelum podium diumumkan.</span></p>

            <template v-if="cat">
                <div class="podium">
                    <section v-for="g in ['M', 'F']" :key="g" class="acard">
                        <div class="section-title"><span>{{ g === 'M' ? 'Putra' : 'Putri' }}</span></div>
                        <ol v-if="cat.podium[g].length" class="pod">
                            <li v-for="(r, i) in cat.podium[g]" :key="r.bib" class="tap" @click="emit('track', r.bib)">
                                <span class="medal" :class="`m${i + 1}`">{{ MEDAL[i] }}</span>
                                <div class="grow"><b>{{ r.name }}</b><small><span class="num">{{ r.bib }}</span> · <span class="num">{{ fmtDur(r.gun_time_ms) }}</span><template v-if="r.needs_review"> · review</template></small></div>
                            </li>
                        </ol>
                        <p v-else class="note">Belum ada finisher.</p>
                    </section>
                </div>

                <div class="section-title"><span>Finisher terbaru</span></div>
                <ul v-if="cat.latest.length" class="alist">
                    <li v-for="r in cat.latest" :key="r.bib" class="tap" @click="emit('track', r.bib)">
                        <b class="bib num">{{ r.bib }}</b>
                        <div class="grow">
                            <b>{{ r.name }}</b>
                            <small>Finish {{ formatTime(r.finish_at_ms) }} · #{{ r.rank_overall }} overall · #{{ r.rank_gender }} {{ r.gender === 'F' ? 'P' : 'L' }}</small>
                        </div>
                        <span class="when"><b class="num">{{ fmtDur(r.gun_time_ms) }}</b><small v-if="r.needs_review" class="hot">review</small></span>
                    </li>
                </ul>
                <p v-else class="empty">Belum ada yang finish di kategori ini.</p>
            </template>
        </template>
    </main>
</template>
