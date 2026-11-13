<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { racepackLookup, racepackCheckin, rosterList, categoryName } from '../store.js';
import { syncSoon } from '../sync.js';
import { feedback, unlockAudio } from '../feedback.js';
import { formatTime } from '../clock.js';
import Icon from './Icon.vue';
import QrReader from './QrReader.vue';

/**
 * Pengambilan racepack, 3 langkah:
 *  1. input  — scan QR e-ticket (nomor registrasi) atau cari nama peserta
 *  2. detail — tampilkan data peserta & status; petugas cek KTP/e-ticket, lalu tekan "Ambil"
 *  3. done   — konfirmasi + ukuran jersey, kembali otomatis ke input
 */
const DONE_MS = 4000;

const step = ref('input');       // input | loading | detail | saving | done
const inputMode = ref(readMode());   // qr | search
const query = ref('');
const roster = ref([]);
const searchBox = ref(null);

function readMode() {
    try { return localStorage.getItem('gtr-racepack-mode') === 'search' ? 'search' : 'qr'; } catch { return 'qr'; }
}
function setMode(m) {
    inputMode.value = m;
    try { localStorage.setItem('gtr-racepack-mode', m); } catch { /* abaikan */ }
    if (m === 'search') setTimeout(() => searchBox.value?.focus(), 50);
}

onMounted(async () => {
    roster.value = await rosterList();
});

/** Huruf kecil tanpa aksen/spasi ganda — "Muḥammad  Rizal" ≈ "muhammad rizal". */
const fold = (s) => String(s ?? '').normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().replace(/\s+/g, ' ').trim();

/** Hasil pencarian nama / nomor registrasi dari data di HP (offline). Awal kata diprioritaskan. */
const results = computed(() => {
    const q = fold(query.value);
    if (q.length < 2) return [];
    const qReg = q.replace(/\s/g, '').toUpperCase();
    return roster.value
        .map((p) => {
            const name = fold(p.name);
            const reg = String(p.registration_number ?? '').toUpperCase();
            const score = name.startsWith(q) ? 0
                : name.split(' ').some((w) => w.startsWith(q)) ? 1
                    : name.includes(q) ? 2
                        : reg && reg.includes(qReg) ? 3 : -1;
            return { p, score };
        })
        .filter((r) => r.score >= 0)
        .sort((a, b) => a.score - b.score || a.p.name.localeCompare(b.p.name))
        .slice(0, 30)
        .map((r) => r.p);
});
const found = ref(null);         // hasil racepackLookup
const proxy = ref(false);        // diwakilkan?
const proxyName = ref('');
const done = ref(null);
let doneTimer;

const STATUS = {
    available: { icon: 'check-circle', title: 'Belum diambil', sub: 'Cek identitas / e-ticket, lalu serahkan race pack.' },
    already: { icon: 'repeat', title: 'Race pack SUDAH diambil', sub: null },
    not_paid: { icon: 'alert', title: 'Pendaftaran belum lunas', sub: 'Arahkan peserta ke meja pembayaran / panitia.' },
    not_found: { icon: 'x-circle', title: 'Peserta tidak ditemukan', sub: 'Cek lagi nomor BIB / QR e-ticket.' },
    invalid: { icon: 'x-circle', title: 'Kode tidak valid', sub: 'Scan QR BIB / e-ticket, atau ketik nomor BIB.' },
};
const SOUND = { available: 'ok', already: 'already', not_paid: 'wrong' };
const GENDER = { M: 'Laki-laki', F: 'Perempuan' };

/** NIK dikelompokkan 4 digit supaya mudah dicocokkan dengan KTP: 5201 0101 0190 0001. */
function formatNik(nik) {
    const s = String(nik ?? '').replace(/\s+/g, '');
    return s ? s.replace(/(.{4})(?=.)/g, '$1 ') : null;
}

const p = computed(() => found.value?.participant ?? null);
const status = computed(() => STATUS[found.value?.status] ?? STATUS.not_found);
const alreadyText = computed(() => {
    const x = p.value;
    if (!x || found.value?.status !== 'already') return null;
    const parts = [];
    if (x.racepack_collected_at_ms) parts.push(formatTime(x.racepack_collected_at_ms));
    if (x.racepack_collected_by) parts.push(`oleh ${x.racepack_collected_by}`);
    if (x.racepack_picked_by) parts.push(`diwakilkan ${x.racepack_picked_by}`);
    return parts.join(' · ') || (found.value.offline ? 'Tercatat di HP ini' : null);
});

async function lookup(raw) {
    unlockAudio();
    step.value = 'loading';
    found.value = await racepackLookup(raw);
    proxy.value = false;
    proxyName.value = '';
    feedback(SOUND[found.value.status] ?? 'unknown');
    step.value = 'detail';
}

async function take() {
    step.value = 'saving';
    const res = await racepackCheckin(found.value.code, proxy.value ? proxyName.value.trim() : null);

    if (res.status === 'ok') {
        feedback('ok');
        done.value = { name: p.value?.name, bib: p.value?.bib, size: p.value?.size, queued: res.queued };
        step.value = 'done';
        clearTimeout(doneTimer);
        doneTimer = setTimeout(reset, DONE_MS);
        syncSoon();
        return;
    }
    // Keduluan petugas lain / status berubah → tampilkan status terbaru.
    found.value = { ...found.value, status: res.status, participant: res.participant ?? p.value };
    feedback(SOUND[res.status] ?? 'unknown');
    step.value = 'detail';
}

function reset() {
    clearTimeout(doneTimer);
    found.value = null;
    done.value = null;
    query.value = '';
    step.value = 'input';
    rosterList().then((list) => { roster.value = list; }); // status racepack terbaru
}

onBeforeUnmount(() => clearTimeout(doneTimer));
</script>

<template>
    <main class="panel" :class="{ 'rp-qr': (step === 'input' || step === 'loading') && inputMode === 'qr' }">
        <!-- 1. Input -->
        <template v-if="step === 'input' || step === 'loading'">
            <div class="seg" role="tablist" aria-label="Cara mencari peserta">
                <button role="tab" :class="{ on: inputMode === 'qr' }" @click="setMode('qr')"><Icon name="camera" :size="18" /> Scan e-ticket</button>
                <button role="tab" :class="{ on: inputMode === 'search' }" @click="setMode('search')"><Icon name="list" :size="18" /> Cari nama</button>
            </div>

            <section v-if="step === 'loading'" class="stage">
                <div class="stage-idle"><Icon name="refresh" :size="30" /><span class="hint">Mencari peserta…</span></div>
            </section>

            <template v-else-if="inputMode === 'qr'">
                <QrReader hint="Arahkan ke QR di e-ticket peserta" @decode="lookup" />
                <p class="tip"><Icon name="card" :size="18" /><span>QR e-ticket ada di email konfirmasi pembayaran / akun Runger App peserta. Tidak bawa e-ticket? Pakai <b>Cari nama</b>.</span></p>
            </template>

            <template v-else>
                <div class="input search sticky">
                    <Icon name="list" :size="20" />
                    <input ref="searchBox" v-model="query" type="search" enterkeyhint="search" autocomplete="off" autocapitalize="words"
                           placeholder="Nama peserta / no. registrasi" @keydown.enter.prevent="results.length === 1 && lookup(results[0].registration_number)">
                    <button v-if="query" class="eye" aria-label="Hapus pencarian" @click="query = ''"><Icon name="x" :size="20" /></button>
                </div>

                <ul v-if="results.length" class="plist results">
                    <li v-for="r in results" :key="r.bib" role="button" tabindex="0" @click="lookup(r.registration_number)" @keydown.enter="lookup(r.registration_number)">
                        <span class="who">
                            <span>{{ r.name }}</span>
                            <small>{{ r.registration_number }} · {{ categoryName(r.category_id) }} · {{ GENDER[r.gender] ?? '-' }}</small>
                        </span>
                        <span v-if="r.racepack" class="st yes"><Icon name="check" :size="16" :stroke="3" /> Diambil</span>
                        <span v-else class="st pick">Pilih <Icon name="chevron-right" :size="16" :stroke="3" /></span>
                    </li>
                </ul>
                <p v-else class="empty">
                    {{ query.trim().length < 2 ? 'Ketik minimal 2 huruf nama peserta.' : (roster.length ? 'Tidak ada peserta dengan nama itu.' : 'Data peserta belum diunduh — buka Data Peserta.') }}
                </p>
            </template>
        </template>

        <!-- 2. Detail peserta -->
        <template v-else-if="step === 'detail' || step === 'saving'">
            <article class="pcard">
                <div class="pcard-status" :class="found.status">
                    <span class="pcard-icon"><Icon :name="status.icon" :size="22" :stroke="2.5" /></span>
                    <div>
                        {{ status.title }}
                        <small v-if="alreadyText || status.sub">{{ alreadyText ?? status.sub }}</small>
                    </div>
                </div>

                <template v-if="p">
                    <div class="pcard-main">
                        <div>
                            <div class="pcard-name">{{ p.name }}</div>
                            <div class="pcard-bib">BIB <b class="num">{{ p.bib ?? '—' }}</b></div>
                        </div>
                        <div v-if="p.size" class="size"><small>JERSEY</small><b>{{ p.size }}</b></div>
                    </div>
                    <div class="idrow" :class="{ missing: !p.nik }">
                        <small><Icon name="card" :size="16" /> No. identitas (NIK) — cocokkan dengan KTP</small>
                        <b class="num">{{ formatNik(p.nik) ?? 'Tidak diisi saat daftar' }}</b>
                    </div>
                    <div class="pcard-tags">
                        <span v-if="p.category" class="tag"><Icon name="flag" :size="16" /> {{ p.category }}</span>
                        <span v-if="p.gender" class="tag"><Icon name="user" :size="16" /> {{ GENDER[p.gender] }}</span>
                        <span v-if="p.club" class="tag"><Icon name="users" :size="16" /> {{ p.club }}</span>
                    </div>
                    <div class="pcard-meta">
                        <span v-if="p.registration_number">No. registrasi {{ p.registration_number }}</span>
                        <span v-if="found.offline" class="offline">Offline — data dari HP. Penyerahan dikirim saat ada sinyal.</span>
                    </div>

                    <template v-if="found.status === 'available'">
                        <label class="toggle">
                            <input v-model="proxy" type="checkbox">
                            Diwakilkan orang lain
                        </label>
                        <div v-if="proxy" class="input">
                            <Icon name="user" :size="20" />
                            <input v-model="proxyName" placeholder="Nama yang mengambil" autocapitalize="words">
                        </div>
                    </template>
                </template>
            </article>

            <!-- Tombol aksi menempel di bawah layar: selalu terlihat walau kartu panjang -->
            <div class="dock">
                <div v-if="found.status === 'available'" class="actions two">
                    <button class="btn lg" :disabled="step === 'saving'" @click="reset">Batal</button>
                    <button class="btn success lg" :disabled="step === 'saving' || (proxy && !proxyName.trim())" @click="take">
                        <Icon name="package" :size="22" /> {{ step === 'saving' ? 'Menyimpan…' : 'Ambil Race Pack' }}
                    </button>
                </div>
                <button v-else class="btn primary lg block" @click="reset">Scan peserta berikutnya <span class="go"><Icon name="chevron-right" :size="20" :stroke="2.75" /></span></button>
            </div>
        </template>

        <!-- 3. Selesai -->
        <template v-else-if="step === 'done'">
            <section class="done" role="status">
                <span class="done-icon"><Icon name="check" :size="44" :stroke="3" /></span>
                <h2>Race pack diserahkan</h2>
                <p>{{ done.name }} · BIB <span class="num">{{ done.bib }}</span></p>
                <div v-if="done.size" class="size"><small>JERSEY</small><b>{{ done.size }}</b></div>
                <p v-if="done.queued" class="done-note">Offline — dikirim otomatis saat ada sinyal.</p>
            </section>
            <div class="dock">
                <button class="btn primary lg block" @click="reset">Scan peserta berikutnya <span class="go"><Icon name="chevron-right" :size="20" :stroke="2.75" /></span></button>
            </div>
        </template>
    </main>
</template>
