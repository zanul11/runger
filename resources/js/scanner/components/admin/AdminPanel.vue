<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { state, adminApi, fireStart } from '../../store.js';
import { nowMs, formatTime } from '../../clock.js';
import { fmtDur, usePoll } from '../../admin.js';
import Icon from '../Icon.vue';
import AdminPosts from './AdminPosts.vue';
import AdminSweep from './AdminSweep.vue';
import AdminTrack from './AdminTrack.vue';
import AdminFinish from './AdminFinish.vue';
import AdminIssues from './AdminIssues.vue';
import AdminReset from './AdminReset.vue';
import AdminPointScans from './AdminPointScans.vue';
import AdminChecklist from './AdminChecklist.vue';

/**
 * Menu Kontrol (admin): START race + ringkasan, dan pintu ke Status pos, Sweep, Lacak,
 * Finish board, Scan bermasalah, Reset data uji.
 */
const emit = defineEmits(['notify']);
const view = ref('home');   // home | checklist | posts | point | sweep | track | finish | issues | reset
const pointId = ref(null);
const returnTo = ref('home'); // layar asal saat membuka lacak dari scan per pos
const trackBib = ref(null);

const { data: race, error, load } = usePoll(() => adminApi('/race'), 15_000);

const now = ref(nowMs());
let tick;
onMounted(() => { tick = setInterval(() => { now.value = nowMs(); }, 250); });
onBeforeUnmount(() => clearInterval(tick));

const firedAt = computed(() => race.value?.fired_at_ms ?? state.pendingStartMs);
const unsent = computed(() => !!state.pendingStartMs && !race.value?.fired_at_ms);
const firedBy = computed(() => race.value?.categories?.find((c) => c.gun_fired_by)?.gun_fired_by);
const scheduled = computed(() => (race.value?.categories ?? []).map((c) => c.gun_start_ms).filter(Boolean).sort()[0] ?? null);
const cats = computed(() => race.value?.categories ?? []);

function timeMs(ms) {
    return ms == null ? '—' : `${formatTime(ms)}.${String(ms % 1000).padStart(3, '0')}`;
}

// ---- START: tahan 1,5 detik; jam start = saat jari MULAI menekan ----
const HOLD_MS = 1500;
const holding = ref(false);
const busy = ref(false);
let holdTimer;
let pressedAt;

function holdStart() {
    if (busy.value || firedAt.value) return;
    pressedAt = nowMs();
    holding.value = true;
    holdTimer = setTimeout(doStart, HOLD_MS);
}
function holdEnd() {
    clearTimeout(holdTimer);
    holding.value = false;
}

async function doStart() {
    holding.value = false;
    busy.value = true;
    navigator.vibrate?.(300);
    try {
        const r = await fireStart(pressedAt);
        if (r.status === 'fired') {
            race.value = r.race;
            emit('notify', `START ${formatTime(pressedAt)} WITA`);
        } else if (r.status === 'already') {
            if (r.race?.categories) race.value = r.race;
            emit('notify', 'Race sudah di-start sebelumnya.');
        } else {
            emit('notify', 'Offline — START tersimpan di HP, dikirim saat ada sinyal.');
        }
    } catch (e) {
        emit('notify', e.message || 'Gagal START');
    } finally {
        busy.value = false;
    }
}

// ---- Koreksi gun time ----
const correcting = ref(false);
const corrTime = ref('');
function openCorrect() {
    corrTime.value = timeMs(firedAt.value);
    correcting.value = !correcting.value;
}
async function saveCorrect() {
    const t = corrTime.value.trim();
    if (!/^\d{1,2}:\d{2}(:\d{2}(\.\d{1,3})?)?$/.test(t)) {
        emit('notify', 'Format jam: 06:00:01.250');
        return;
    }
    if (!confirm(`Ubah gun time SEMUA kategori ke ${t} WITA? Semua hasil dihitung ulang.`)) return;
    busy.value = true;
    try {
        race.value = await adminApi('/start/correct', { method: 'POST', body: { time: t } });
        correcting.value = false;
        emit('notify', race.value.message);
    } catch (e) {
        emit('notify', e.message || 'Gagal menyimpan koreksi');
    } finally {
        busy.value = false;
    }
}

/** Hitung mundur: "15 hari 14:54:09" bila ≥ 1 hari, selain itu "02:54:09". */
const countdown = (ms) => (ms >= 86_400_000 ? `${Math.floor(ms / 86_400_000)} hari ${fmtDur(ms % 86_400_000)}` : fmtDur(ms));

const badge = (key) => race.value?.badges?.[key] ?? 0;

function open(v, bib = null, keepReturn = false) {
    if (!keepReturn) returnTo.value = 'home';
    trackBib.value = bib;
    view.value = v;
}
function back() {
    view.value = 'home';
    load();
}
</script>

<template>
    <AdminPosts v-if="view === 'posts'" @back="back" @notify="emit('notify', $event)" @point="pointId = $event; view = 'point'" />
    <AdminPointScans v-else-if="view === 'point'" :point-id="pointId" @back="view = 'posts'" @notify="emit('notify', $event)"
                     @track="returnTo = 'point'; open('track', $event, true)" />
    <AdminSweep v-else-if="view === 'sweep'" @back="back" @track="open('track', $event)" />
    <AdminTrack v-else-if="view === 'track'" :bib="trackBib" @back="returnTo === 'point' ? (view = 'point', returnTo = 'home') : back()" @notify="emit('notify', $event)" />
    <AdminFinish v-else-if="view === 'finish'" @back="back" @track="open('track', $event)" />
    <AdminIssues v-else-if="view === 'issues'" @back="back" @track="open('track', $event)" @notify="emit('notify', $event)" />
    <AdminReset v-else-if="view === 'reset'" @back="back" @notify="emit('notify', $event)" />
    <AdminChecklist v-else-if="view === 'checklist'" @back="back" @open="open($event)" />

    <main v-else class="panel admin">
        <div v-if="error && !race" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <!-- START / jam race -->
        <section class="race-card" :class="firedAt ? 'live' : 'idle'">
            <template v-if="firedAt">
                <small>Race berjalan</small>
                <b class="race-clock num">{{ fmtDur(Math.max(0, now - firedAt)) }}</b>
                <span class="race-sub">START <b class="num">{{ timeMs(firedAt) }}</b> WITA<template v-if="firedBy"> · {{ firedBy }}</template></span>
                <span v-if="unsent" class="tag warn"><Icon name="upload" :size="16" /> START belum terkirim — menunggu sinyal</span>
                <button class="btn sm" :disabled="busy || unsent || !state.online" @click="openCorrect"><Icon name="clock" :size="18" /> Koreksi gun time</button>
                <div v-if="correcting" class="correct">
                    <div class="input">
                        <input v-model="corrTime" inputmode="decimal" placeholder="06:00:01.250" autocomplete="off" aria-label="Jam tembak (WITA)">
                    </div>
                    <button class="btn primary" :disabled="busy" @click="saveCorrect">Simpan</button>
                </div>
            </template>
            <template v-else>
                <small>Jadwal start</small>
                <b class="race-clock num">{{ scheduled ? formatTime(scheduled).slice(0, 5) : '—' }}</b>
                <span v-if="scheduled && scheduled > now" class="race-sub">dalam <b class="num">{{ countdown(scheduled - now) }}</b></span>
                <span v-if="cats.length" class="race-sub">
                    <template v-for="(c, i) in cats" :key="c.id">{{ i ? ' · ' : '' }}{{ c.name }} <b class="num">{{ c.eligible }}</b></template> peserta
                </span>
                <button class="hold" :class="{ holding }" :disabled="busy"
                        @pointerdown.prevent="holdStart" @pointerup="holdEnd" @pointerleave="holdEnd" @pointercancel="holdEnd" @contextmenu.prevent>
                    <span class="hold-fill"></span>
                    <span class="hold-label"><Icon name="play" :size="22" /> {{ busy ? 'Mencatat…' : holding ? 'Tahan…' : 'Tahan untuk START' }}</span>
                </button>
                <p class="race-hint">Jam start = saat jari mulai menekan. Tahan 1,5 detik — semua kategori start bersama.</p>
            </template>
        </section>

        <!-- Peringatan: ketuk untuk membuka layar terkait -->
        <div v-if="race?.alerts?.length" class="alerts">
            <button v-for="(a, i) in race.alerts" :key="i" class="alert" :class="a.level" @click="open(a.view)">
                <Icon :name="a.level === 'bad' ? 'x-circle' : 'alert'" :size="20" />
                <span class="grow">{{ a.text }}</span>
                <Icon name="chevron-right" :size="18" />
            </button>
        </div>

        <div v-if="race" class="stats three">
            <div class="stat dark"><small>Finisher</small><b class="num">{{ race.results.finisher }}</b></div>
            <div class="stat" :class="{ hot: race.results.review }"><small>Perlu review</small><b class="num">{{ race.results.review }}</b></div>
            <div class="stat" :class="{ hot: race.issues }"><small>Scan bermasalah</small><b class="num">{{ race.issues }}</b></div>
        </div>

        <div class="tiles">
            <button v-if="!firedAt" class="tile" @click="open('checklist')">
                <span class="tile-ic" :class="{ hot: badge('checklist') }"><Icon name="check-circle" :size="20" /></span>
                <b v-if="badge('checklist')" class="tile-badge num">{{ badge('checklist') }}</b>
                <span>Checklist<small>Kesiapan sebelum START</small></span>
            </button>
            <button class="tile" @click="open('posts')">
                <span class="tile-ic" :class="{ hot: badge('posts') }"><Icon name="activity" :size="20" /></span>
                <b v-if="badge('posts')" class="tile-badge num">{{ badge('posts') }}</b>
                <span>Status pos<small>Scan per pos · HP marshal</small></span>
            </button>
            <button class="tile" @click="open('sweep')">
                <span class="tile-ic" :class="{ hot: badge('sweep') }"><Icon name="flag" :size="20" /></span>
                <b v-if="badge('sweep')" class="tile-badge num">{{ badge('sweep') }}</b>
                <span>Sweep<small>Belum lewat CP terakhir</small></span>
            </button>
            <button class="tile" @click="open('track')"><span class="tile-ic"><Icon name="search" :size="20" /></span><span>Lacak peserta<small>BIB / nama → posisi</small></span></button>
            <button class="tile" @click="open('finish')"><span class="tile-ic"><Icon name="trophy" :size="20" /></span><span>Finish board<small>Podium & finisher terbaru</small></span></button>
            <button class="tile" @click="open('issues')">
                <span class="tile-ic" :class="{ hot: race?.issues }"><Icon name="alert" :size="20" /></span>
                <b v-if="race?.issues" class="tile-badge num">{{ race.issues }}</b>
                <span>Scan bermasalah<small>{{ race?.issues ? `${race.issues} perlu dicek` : 'Perbaiki BIB · abaikan' }}</small></span>
            </button>
            <button class="tile danger" @click="open('reset')"><span class="tile-ic"><Icon name="trash" :size="20" /></span><span>Reset data uji<small>Pakai kode reset</small></span></button>
            <button v-if="firedAt" class="tile" @click="open('checklist')"><span class="tile-ic"><Icon name="check-circle" :size="20" /></span><span>Checklist<small>Kesiapan & kondisi HP</small></span></button>
        </div>

        <p v-if="race && !race.results_published" class="tip"><Icon name="lock" :size="18" /><span>Hasil <b>belum dipublikasikan</b> — angka di sini masih sementara. Publikasi hasil dari admin web.</span></p>
    </main>
</template>
