<script setup>
import { computed } from 'vue';
import { adminApi } from '../../store.js';
import { ago, usePoll } from '../../admin.js';
import Icon from '../Icon.vue';

/** Status pos: peserta tercatat per pos × kategori, scan terakhir, dan HP marshal. */
const emit = defineEmits(['back', 'notify', 'point']);
const { data, error, loading, load } = usePoll(() => adminApi('/posts'), 20_000);

const catName = computed(() => Object.fromEntries((data.value?.categories ?? []).map((c) => [c.id, c.name])));
const DEVICE = { ok: 'Aktif', quiet: 'Diam ≥ 10 mnt', stale: 'Diam ≥ 20 mnt', idle: 'Belum kirim' };
const pct = (c) => (c.total ? Math.round((c.seen / c.total) * 100) : 0);
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Status Pos</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <p class="note">Ketuk pos untuk melihat & mengoreksi scan-nya.</p>
            <section v-for="r in data.rows" :key="r.code" class="acard" :class="{ tap: r.type !== 'start' }"
                     @click="r.type !== 'start' && emit('point', r.id)">
                <div class="acard-head">
                    <span class="code">{{ r.code }}</span>
                    <div class="grow">
                        <b>{{ r.name }}</b>
                        <small>{{ r.marshals.length ? r.marshals.join(', ') : 'Belum ada marshal' }}</small>
                    </div>
                    <span class="ago" :class="{ none: !r.last_at_ms }">{{ r.last_at_ms ? ago(r.last_at_ms) : 'belum ada scan' }}</span>
                    <Icon v-if="r.type !== 'start'" name="chevron-right" :size="20" />
                </div>
                <template v-for="c in r.cells" :key="c?.category_id">
                    <div v-if="c" class="meter">
                        <span>{{ catName[c.category_id] }}</span>
                        <b class="num">{{ c.seen }} / {{ c.total }}</b>
                        <div class="bar"><div :style="{ width: pct(c) + '%' }"></div></div>
                    </div>
                </template>
                <p v-if="r.type === 'start'" class="note">Start memakai gun time — tidak ada scan di garis start.</p>
            </section>

            <div class="section-title"><span>HP marshal</span><span>{{ data.devices.length }}</span></div>
            <ul v-if="data.devices.length" class="alist">
                <li v-for="d in data.devices" :key="d.device_id">
                    <span class="dot" :class="d.state" :title="DEVICE[d.state]"></span>
                    <div class="grow">
                        <b>{{ d.user ?? 'Tanpa nama' }}</b>
                        <small>{{ d.point }} · {{ d.total }} scan · {{ DEVICE[d.state] }}</small>
                    </div>
                    <span class="ago">{{ ago(d.last_received_ms) }}</span>
                </li>
            </ul>
            <p v-else class="empty">Belum ada HP marshal yang login / mengirim scan.</p>
        </template>
    </main>
</template>
