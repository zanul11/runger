<script setup>
import { computed, ref } from 'vue';
import { consumption, filledCount, isFilled, matches, positionsOf, shortfall, totals } from './logic.js';

/**
 * Roster versi LIHAT SAJA yang sederhana: daftar pos → nama petugas, status "✓" atau
 * "kurang N". Dipakai halaman publik /crew/roster dan akun GTR Crew yang tidak bisa mengisi.
 * Data & polling dari RosterBoard.
 */
const props = defineProps({
    data: { type: Object, required: true },
    tamu: { type: [Number, String], default: 0 },
    showBack: { type: Boolean, default: false },
    error: { type: String, default: null },
});
const emit = defineEmits(['back']);

const ev = computed(() => props.data.event ?? {});
const total = computed(() => totals((props.data.areas ?? []).flatMap(positionsOf)));
const cons = computed(() => consumption(props.data, props.tamu));

const q = ref('');
const tab = ref('all');
const onlyShort = ref(false);
const view = computed(() => (props.data.areas ?? [])
    .filter((a) => tab.value === 'all' || a.id === tab.value)
    .map((a) => ({
        ...a,
        t: totals(positionsOf(a)),
        groups: a.groups
            .map((g) => ({ ...g, positions: g.positions.filter((p) => (!onlyShort.value || shortfall(p) > 0) && matches(p, g, a, q.value)) }))
            .filter((g) => g.positions.length),
    }))
    .filter((a) => a.groups.length));
const filtering = computed(() => !!q.value.trim() || onlyShort.value);

const names = (p) => p.slots.slice(0, p.max).filter(isFilled);
// No. HP lengkap (akun GTR Crew) bisa langsung ditelepon; versi publik disamarkan.
const tel = (hp) => (hp && !hp.includes('•') ? `tel:${hp.replace(/[^\d+]/g, '')}` : null);
</script>

<template>
    <div class="rs">
        <header class="rs-head">
            <div class="rg-wrap">
                <button v-if="props.showBack" class="rg-back" @click="emit('back')">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M15 18l-6-6 6-6" /></svg> GTR Crew
                </button>
                <small class="rs-kicker">Roster Panitia Hari H</small>
                <h1>{{ ev.nama }}</h1>
                <p class="rs-info">{{ [ev.tanggal_label, ev.lokasi_race_village].filter(Boolean).join(' · ') }}</p>
                <p class="rs-total">
                    <b class="num">{{ total.filled }}</b> petugas terisi
                    <span class="rs-pill" :class="total.short ? 'bad' : 'ok'">{{ total.short ? `kurang ${total.short} orang` : 'lengkap ✓' }}</span>
                </p>
            </div>
        </header>

        <div class="rs-bar">
            <div class="rg-wrap">
                <input v-model="q" type="search" class="rs-search" placeholder="Cari nama, pos, atau KM…" autocomplete="off" aria-label="Cari">
                <div class="rs-chips">
                    <button :class="{ on: tab === 'all' }" @click="tab = 'all'">Semua</button>
                    <button v-for="a in data.areas" :key="a.id" :class="{ on: tab === a.id }" @click="tab = a.id">{{ a.nama }}</button>
                    <button :class="{ on: onlyShort }" @click="onlyShort = !onlyShort">Belum lengkap</button>
                </div>
            </div>
        </div>

        <main class="rg-wrap rs-main">
            <p v-if="error" class="rg-alert">{{ error }}</p>
            <p v-if="!data.areas.length" class="rg-empty">Roster belum disusun.</p>
            <p v-else-if="filtering && !view.length" class="rg-empty">Tidak ada yang cocok.</p>

            <section v-for="a in view" :key="a.id" class="rs-area">
                <h2>{{ a.nama }} <small class="num">{{ a.t.filled }} orang<template v-if="a.t.short"> · kurang {{ a.t.short }}</template></small></h2>
                <div class="rs-groups">
                    <div v-for="g in a.groups" :key="g.id" class="rs-group">
                        <h3>{{ g.nama }}</h3>
                        <ul>
                            <li v-for="p in g.positions" :key="p.id" class="rs-pos">
                                <div class="rs-row">
                                    <b class="rs-name">{{ p.nama }}</b>
                                    <span v-if="shortfall(p)" class="rs-pill bad">kurang {{ shortfall(p) }}</span>
                                    <span v-else-if="filledCount(p)" class="rs-pill ok num">✓ {{ filledCount(p) }}</span>
                                    <span v-else class="rs-pill">opsional</span>
                                </div>
                                <small v-if="p.km.length" class="rs-km">KM {{ p.km.join(' · ') }}</small>
                                <small v-if="p.deskripsi" class="rs-job">{{ p.deskripsi }}</small>
                                <ol v-if="names(p).length" class="rs-people">
                                    <li v-for="s in names(p)" :key="s.slot_no">
                                        {{ s.nama }}
                                        <a v-if="tel(s.no_hp)" :href="tel(s.no_hp)" class="num">{{ s.no_hp }}</a>
                                        <span v-else-if="s.no_hp" class="num">{{ s.no_hp }}</span>
                                    </li>
                                </ol>
                                <p v-else class="rs-none">Belum ada petugas</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section v-if="data.areas.length && !filtering && tab === 'all'" class="rs-area">
                <h2>Konsumsi</h2>
                <div class="rs-group rs-cons">
                    <p>Sarapan <b class="num">{{ cons.sarapan }}</b> · Makan siang <b class="num">{{ cons.makanSiang }}</b> · Snack <b class="num">{{ cons.snack }}</b> · Air <b class="num">{{ cons.air }}</b> botol<template v-if="cons.lapanganNama"> · Paket ke pos {{ cons.lapanganNama.toLowerCase() }} <b class="num">{{ cons.lapangan }}</b></template></p>
                    <ul v-if="data.consumption.jadwal.length" class="rs-jadwal">
                        <li v-for="(j, i) in data.consumption.jadwal" :key="i"><b class="num">{{ j.waktu }}</b> {{ j.kegiatan }}</li>
                    </ul>
                </div>
            </section>

            <p class="rg-foot">
                Halaman ini hanya untuk dilihat dan diperbarui otomatis.
                <template v-if="data.public">Pengisian petugas lewat <a href="/crew/app">GTR Crew</a> dengan akun panitia.</template>
            </p>
        </main>
    </div>
</template>
