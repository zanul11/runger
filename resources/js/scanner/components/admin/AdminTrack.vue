<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { adminApi } from '../../store.js';
import { formatTime, formatDayTime } from '../../clock.js';
import { STATUS, ago, fmtDur, waLink } from '../../admin.js';
import Icon from '../Icon.vue';
import ScanRow from './ScanRow.vue';

/**
 * Lacak peserta: cari BIB / nama → timeline per pos (jam lewat, waktu tempuh, cut off),
 * hasil sementara, kontak darurat, dan semua scan peserta (abaikan / pulihkan).
 */
const props = defineProps({ bib: { type: String, default: null } });
const emit = defineEmits(['back', 'notify']);

const q = ref('');
const results = ref([]);
const searching = ref(false);
const detail = ref(null);
const loadingDetail = ref(false);
const error = ref(null);
let debounce;
let poll;

watch(q, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => search(v), 300);
});

async function search(term) {
    term = term.trim();
    if (!term) {
        results.value = [];
        return;
    }
    searching.value = true;
    try {
        results.value = (await adminApi('/participants?q=' + encodeURIComponent(term))).participants;
        error.value = null;
    } catch (e) {
        error.value = e.message || 'Gagal mencari';
    } finally {
        searching.value = false;
    }
}

async function openDetail(bib, quiet = false) {
    if (!quiet) loadingDetail.value = true;
    try {
        detail.value = await adminApi('/participants/' + encodeURIComponent(bib));
        error.value = null;
    } catch (e) {
        if (!quiet) error.value = e.status === 404 ? `BIB ${bib} tidak terdaftar / belum lunas.` : (e.message || 'Gagal memuat');
    } finally {
        loadingDetail.value = false;
    }
    clearInterval(poll);
    poll = setInterval(() => {
        if (detail.value && document.visibilityState === 'visible' && navigator.onLine) openDetail(detail.value.bib, true);
    }, 20_000);
}

function closeDetail() {
    clearInterval(poll);
    if (props.bib && !results.value.length) {
        emit('back');
        return;
    }
    detail.value = null;
}
onBeforeUnmount(() => {
    clearInterval(poll);
    clearTimeout(debounce);
});

if (props.bib) openDetail(props.bib);

const t = computed(() => detail.value?.track);
const status = computed(() => STATUS[t.value?.status] ?? { label: t.value?.status, cls: 'todo' });
const scans = computed(() => detail.value?.scans ?? []);

// ---- Ubah status (mundur / dievakuasi / DQ) ----
const STATUS_OPTS = [
    { key: 'dnf', label: 'DNF', hint: 'Mundur / dievakuasi' },
    { key: 'dq', label: 'DQ', hint: 'Diskualifikasi' },
    { key: 'dns', label: 'DNS', hint: 'Tidak start' },
    { key: 'finisher', label: 'Finisher', hint: 'Sah finish' },
];
const editing = ref(false);
const newStatus = ref('dnf');
const reason = ref('');
const saving = ref(false);

function openStatus() {
    newStatus.value = detail.value.manual_status?.status ?? 'dnf';
    reason.value = '';
    editing.value = !editing.value;
}

async function saveStatus(status) {
    if (status !== 'auto' && !reason.value.trim()) {
        emit('notify', 'Isi alasan (mis. cedera, dievakuasi di CP2).');
        return;
    }
    const msg = status === 'auto'
        ? `Cabut status manual BIB ${detail.value.bib}? Status kembali mengikuti scan.`
        : `Tetapkan BIB ${detail.value.bib} ${status.toUpperCase()}?`;
    if (!confirm(msg)) return;
    saving.value = true;
    try {
        const res = await adminApi(`/participants/${encodeURIComponent(detail.value.bib)}/status`, {
            method: 'POST', body: { status, reason: status === 'auto' ? null : reason.value.trim() },
        });
        emit('notify', res.message);
        editing.value = false;
        await openDetail(detail.value.bib, true);
    } catch (e) {
        emit('notify', e.message || 'Gagal menyimpan status');
    } finally {
        saving.value = false;
    }
}

function onChanged(scan) {
    const i = detail.value.scans.findIndex((s) => s.id === scan.id);
    if (i >= 0) detail.value.scans[i] = scan;
    openDetail(detail.value.bib, true);
}
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button class="btn sm icon-only" aria-label="Kembali" @click="detail ? closeDetail() : emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>{{ detail ? `BIB ${detail.bib}` : 'Lacak Peserta' }}</h2>
        </div>
        <div v-if="error" class="alert warn"><Icon name="alert" :size="20" /><span>{{ error }}</span></div>

        <!-- Pencarian -->
        <template v-if="!detail">
            <div class="input search sticky">
                <Icon name="search" :size="20" />
                <input v-model="q" type="search" inputmode="search" placeholder="BIB atau nama peserta" autocomplete="off" autofocus>
                <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
            </div>
            <ul v-if="results.length" class="plist results">
                <li v-for="p in results" :key="p.bib" @click="openDetail(p.bib)">
                    <span class="who">
                        <span><b class="num">{{ p.bib }}</b> · {{ p.name }}</span>
                        <small>{{ p.category }} · {{ p.gender === 'F' ? 'P' : 'L' }}<template v-if="p.last_point"> · terakhir {{ p.last_point }} {{ formatTime(p.last_at_ms) }}</template></small>
                    </span>
                    <span class="st" :class="STATUS[p.status]?.cls">{{ STATUS[p.status]?.label ?? p.status }}</span>
                </li>
            </ul>
            <p v-else-if="q && !searching" class="empty">Tidak ada peserta yang cocok.</p>
            <p v-else-if="!q" class="empty">Ketik nomor BIB atau nama.</p>
        </template>

        <p v-if="loadingDetail && !detail" class="empty">Memuat…</p>

        <!-- Detail -->
        <template v-if="detail && t">
            <section class="acard">
                <div class="acard-head">
                    <span class="code">{{ t.category.name }}</span>
                    <div class="grow">
                        <b class="name">{{ t.name }}</b>
                        <small>{{ detail.gender === 'F' ? 'Perempuan' : 'Laki-laki' }}<template v-if="t.club"> · {{ t.club }}</template></small>
                    </div>
                    <span class="st" :class="status.cls">{{ status.label }}</span>
                </div>
                <div class="kv">
                    <span>Start</span><b class="num">{{ t.start_at ?? '—' }}</b>
                    <template v-if="t.finish_at"><span>Finish</span><b class="num">{{ t.finish_at }} · {{ fmtDur(t.net * 1000) }}</b></template>
                    <template v-else-if="t.status === 'on_course' && t.start_ts"><span>Waktu tempuh</span><b class="num">{{ fmtDur(t.now_ts - t.start_ts) }}</b></template>
                    <template v-if="t.estimate"><span>Perkiraan posisi</span><b>KM {{ t.estimate.km }}<template v-if="t.estimate.next"> → {{ t.estimate.next }}</template></b></template>
                    <template v-if="detail.result"><span>Hasil (sementara)</span><b>{{ STATUS[detail.result.status]?.label ?? detail.result.status }}<template v-if="detail.result.rank_overall"> · #{{ detail.result.rank_overall }} overall · #{{ detail.result.rank_gender }} {{ detail.gender === 'F' ? 'P' : 'L' }}</template></b></template>
                </div>
                <p v-if="detail.result?.needs_review" class="alert warn"><Icon name="alert" :size="18" /><span>Perlu review: {{ detail.result.review_note }}</span></p>
                <p v-if="detail.manual_status" class="alert bad">
                    <Icon name="flag" :size="18" />
                    <span>Status ditetapkan <b>{{ detail.manual_status.status.toUpperCase() }}</b> — {{ detail.manual_status.reason }}<template v-if="detail.manual_status.by"> · {{ detail.manual_status.by }}</template> · {{ formatTime(detail.manual_status.at_ms) }}</span>
                </p>
                <button class="btn block" :disabled="saving" @click="openStatus"><Icon name="flag" :size="18" /> Ubah status</button>
                <div v-if="editing" class="statusform">
                    <div class="seg">
                        <button v-for="o in STATUS_OPTS" :key="o.key" :class="{ on: newStatus === o.key }" @click="newStatus = o.key">{{ o.label }}</button>
                    </div>
                    <small class="note">{{ STATUS_OPTS.find((o) => o.key === newStatus)?.hint }} · peserta DNF/DQ/DNS tidak dicari lagi di Sweep.</small>
                    <div class="input"><input v-model="reason" placeholder="Alasan (wajib), mis. cedera — dievakuasi di CP2" autocomplete="off"></div>
                    <button class="btn primary block" :disabled="saving" @click="saveStatus(newStatus)">{{ saving ? 'Menyimpan…' : `Tetapkan ${newStatus.toUpperCase()}` }}</button>
                    <button v-if="detail.manual_status" class="btn ghost block" :disabled="saving" @click="saveStatus('auto')">Cabut status manual (ikuti scan)</button>
                </div>
            </section>

            <div class="section-title"><span>Racepack</span></div>
            <section class="acard">
                <div class="acard-head">
                    <span class="st" :class="detail.racepack.collected ? 'yes' : 'todo'">{{ detail.racepack.collected ? 'Sudah diambil' : 'Belum diambil' }}</span>
                    <span class="grow"></span>
                    <span v-if="detail.racepack.size" class="ago">Jersey {{ detail.racepack.size }}</span>
                </div>
                <div v-if="detail.racepack.collected" class="kv">
                    <span>Waktu</span><b class="num">{{ formatDayTime(detail.racepack.at_ms) }} WITA</b>
                    <span>Petugas</span><b>{{ detail.racepack.by ?? '—' }}</b>
                    <span>Pengambil</span><b>{{ detail.racepack.picked_by ? `${detail.racepack.picked_by} (diwakilkan)` : 'Peserta sendiri' }}</b>
                </div>
            </section>

            <div class="section-title"><span>Timeline pos</span></div>
            <ol class="timeline">
                <li v-for="p in t.passes" :key="`${p.id}-${p.visit}`" :class="{ passed: p.passed }">
                    <span class="code">{{ p.code }}</span>
                    <div class="grow">
                        <b>{{ p.name }}</b>
                        <small><template v-if="p.km != null">KM {{ p.km }}</template><template v-if="p.cutoff"> · COT {{ p.cutoff }}</template></small>
                    </div>
                    <span v-if="p.passed" class="when"><b class="num">{{ p.at }}</b><small v-if="p.elapsed != null" class="num">{{ fmtDur(p.elapsed * 1000) }}</small></span>
                    <span v-else class="when muted">—</span>
                </li>
            </ol>

            <div class="section-title"><span>Kontak</span></div>
            <section class="acard contact">
                <div v-if="detail.contact.phone" class="crow">
                    <div class="grow"><small>Peserta</small><b class="num">{{ detail.contact.phone }}</b></div>
                    <a class="round" :href="`tel:${detail.contact.phone}`" aria-label="Telepon peserta"><Icon name="phone" :size="18" /></a>
                    <a class="round" :href="waLink(detail.contact.phone)" target="_blank" rel="noopener" aria-label="WhatsApp peserta"><Icon name="message" :size="18" /></a>
                </div>
                <div v-if="detail.contact.emergency_phone" class="crow">
                    <div class="grow"><small>Darurat · {{ detail.contact.emergency_name ?? '-' }}</small><b class="num">{{ detail.contact.emergency_phone }}</b></div>
                    <a class="round sos" :href="`tel:${detail.contact.emergency_phone}`" aria-label="Telepon kontak darurat"><Icon name="phone" :size="18" /></a>
                    <a class="round" :href="waLink(detail.contact.emergency_phone)" target="_blank" rel="noopener" aria-label="WhatsApp kontak darurat"><Icon name="message" :size="18" /></a>
                </div>
                <div v-if="detail.contact.blood_type" class="crow"><div class="grow"><small>Golongan darah</small><b>{{ detail.contact.blood_type }}</b></div></div>
            </section>

            <template v-if="detail.adjustments.length">
                <div class="section-title"><span>Koreksi hasil aktif</span></div>
                <ul class="alist">
                    <li v-for="(a, i) in detail.adjustments" :key="i">
                        <div class="grow"><b>{{ a.field }}: {{ a.old ?? '—' }} → {{ a.new ?? '—' }}</b><small>{{ a.reason }}<template v-if="a.by"> · {{ a.by }}</template></small></div>
                    </li>
                </ul>
            </template>

            <div class="section-title"><span>Semua scan</span><span>{{ scans.length }}</span></div>
            <ul v-if="scans.length" class="alist scans">
                <ScanRow v-for="s in scans" :key="s.id" :scan="s" :show-name="false" @changed="onChanged" @notify="emit('notify', $event)" />
            </ul>
            <p v-else class="empty">Belum ada scan untuk peserta ini.</p>
            <p class="device">Diperbarui {{ t.updated }} WITA · {{ ago(t.now_ts) }}</p>
        </template>
    </main>
</template>
