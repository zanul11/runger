<script setup>
import { computed, ref } from 'vue';
import { api } from '../api.js';
import { state } from '../store.js';
import { formatDayTime } from '../clock.js';
import { usePoll } from '../admin.js';
import Icon from './Icon.vue';

/**
 * Checklist persiapan panitia (perlengkapan, pos, medis, racepack, dll).
 * Semua akun GTR Crew: lihat. Role panitia (& admin): centang, tambah, ubah, hapus, isi template.
 * Versi publik baca-saja untuk dibagikan: /crew/persiapan.
 */
// standalone = layar utama (akun panitia): tanpa tombol kembali.
const props = defineProps({ standalone: { type: Boolean, default: false } });
const emit = defineEmits(['back', 'notify']);
const prepApi = (path = '', opts = {}) => api('/prep' + path, { token: state.session?.token, timeout: 15000, ...opts });
const { data, error, loading, load } = usePoll(() => prepApi(), 30_000);
const canEdit = computed(() => ['panitia', 'admin'].includes(state.session?.user?.role));
const PUBLIC_URL = `${location.origin}/crew/persiapan`;

async function share() {
    try {
        if (navigator.share) {
            await navigator.share({ title: 'Persiapan Panitia GTR 2026', text: 'Checklist persiapan panitia GTR:', url: PUBLIC_URL });
            return;
        }
        await navigator.clipboard.writeText(PUBLIC_URL);
        emit('notify', 'Link disalin — tempel di grup WA panitia.');
    } catch (e) {
        if (e?.name !== 'AbortError') emit('notify', PUBLIC_URL);
    }
}

const q = ref('');
const phase = ref('all');
const onlyOpen = ref(false);

const PHASE_ORDER = ['h7', 'h3', 'h1', 'race', 'post'];
const phases = computed(() => data.value?.phases ?? {});

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    return (data.value?.items ?? []).filter((i) => (phase.value === 'all' || i.phase === phase.value)
        && (!onlyOpen.value || !i.done)
        && (!term || [i.title, i.pic, i.location, i.category, i.qty].some((v) => v?.toLowerCase().includes(term))));
});
// Sedang cari / filter → kategori tanpa hasil disembunyikan, yang ada hasilnya terbuka otomatis.
const filtering = computed(() => !!q.value.trim() || phase.value !== 'all' || onlyOpen.value);
const groups = computed(() => (data.value?.categories ?? [])
    .map((c) => {
        const all = (data.value?.items ?? []).filter((i) => i.category === c);
        const done = all.filter((i) => i.done).length;
        return { name: c, items: filtered.value.filter((i) => i.category === c), done, total: all.length, pct: all.length ? Math.round((done / all.length) * 100) : 0 };
    })
    .filter((g) => !filtering.value || g.items.length));

// Akordeon: tampilan awal = daftar kategori + progres; ketuk untuk membuka item.
const openCats = ref([]);
const isOpen = (name) => filtering.value || openCats.value.includes(name);
function toggleCat(name) {
    if (filtering.value) return;
    openCats.value = openCats.value.includes(name) ? openCats.value.filter((c) => c !== name) : [...openCats.value, name];
}
const pct = computed(() => (data.value?.total ? Math.round((data.value.done / data.value.total) * 100) : 0));

// ---- centang (langsung tampil, dibatalkan bila gagal) ----
const busyId = ref(null);
async function toggle(item) {
    if (busyId.value || !canEdit.value) return;
    const before = { ...item };
    busyId.value = item.id;
    item.done = !item.done;
    data.value.done += item.done ? 1 : -1;
    try {
        const res = await prepApi(`/${item.id}/check`, { method: 'POST', body: { done: item.done } });
        Object.assign(item, res.item);
    } catch (e) {
        Object.assign(item, before);
        data.value.done += before.done ? 1 : -1;
        emit('notify', state.online ? (e.message || 'Gagal menyimpan') : 'Offline — centang butuh sinyal');
    } finally {
        busyId.value = null;
    }
}

// ---- form tambah / ubah (admin) ----
const form = ref(null);   // null | { id?, category, title, qty, pic, location, phase, notes }
const saving = ref(false);

function openForm(item = null) {
    if (!canEdit.value) return;
    form.value = item
        ? { id: item.id, category: item.category, title: item.title, qty: item.qty ?? '', pic: item.pic ?? '', location: item.location ?? '', phase: item.phase ?? '', notes: item.notes ?? '' }
        : { category: data.value?.categories?.[0] ?? '', title: '', qty: '', pic: '', location: '', phase: '', notes: '' };
}

async function save() {
    const f = form.value;
    if (!f.category.trim() || !f.title.trim()) {
        emit('notify', 'Kategori dan nama item wajib diisi.');
        return;
    }
    const body = Object.fromEntries(['category', 'title', 'qty', 'pic', 'location', 'phase', 'notes'].map((k) => [k, f[k]?.trim?.() || null]));
    saving.value = true;
    try {
        await prepApi(f.id ? `/${f.id}` : '', { method: f.id ? 'PUT' : 'POST', body });
        emit('notify', f.id ? 'Item diperbarui.' : 'Item ditambahkan.');
        form.value = null;
        await load();
    } catch (e) {
        emit('notify', e.message || 'Gagal menyimpan');
    } finally {
        saving.value = false;
    }
}

async function remove() {
    if (!confirm(`Hapus "${form.value.title}"?`)) return;
    saving.value = true;
    try {
        await prepApi(`/${form.value.id}`, { method: 'DELETE' });
        emit('notify', 'Item dihapus.');
        form.value = null;
        await load();
    } catch (e) {
        emit('notify', e.message || 'Gagal menghapus');
    } finally {
        saving.value = false;
    }
}

async function fillTemplate() {
    const msg = data.value?.total
        ? 'Tambahkan item template yang belum ada (mis. Surat & Undangan)? Item yang sudah ada tidak diubah.'
        : 'Isi checklist dengan template persiapan trail run? Setelah itu semua item bisa diubah / dihapus.';
    if (!confirm(msg)) return;
    try {
        const res = await prepApi('/template', { method: 'POST' });
        emit('notify', res.message);
        await load();
    } catch (e) {
        emit('notify', e.message || 'Gagal mengisi template');
    }
}

function meta(i) {
    return [i.qty, i.location, i.pic ? `PIC ${i.pic}` : null, phases.value[i.phase]].filter(Boolean).join(' · ');
}
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button v-if="!props.standalone" class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Persiapan Panitia</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <section class="acard">
                <div class="meter">
                    <span>Selesai</span>
                    <b class="num">{{ data.done }} / {{ data.total }} · {{ pct }}%</b>
                    <div class="bar"><div :style="{ width: pct + '%' }"></div></div>
                </div>
                <button v-if="canEdit" class="btn block" @click="openForm()"><Icon name="plus-square" :size="20" /> Tambah item</button>
                <button class="btn block ghost" @click="share"><Icon name="share" :size="18" /> Bagikan link publik (tanpa login)</button>
                <p v-if="!canEdit" class="note">Hanya akun <b>panitia</b> yang bisa mencentang & mengubah checklist.</p>
            </section>

            <div v-if="!data.total" class="acard empty-prep">
                <p class="note">Checklist masih kosong.</p>
                <button v-if="canEdit" class="btn primary block" @click="fillTemplate"><Icon name="list" :size="20" /> Isi template persiapan trail run</button>
                <p v-else class="note">Panitia belum mengisi checklist persiapan.</p>
            </div>

            <template v-else>
                <div class="input search sticky">
                    <Icon name="search" :size="20" />
                    <input v-model="q" type="search" inputmode="search" placeholder="Cari item, PIC, lokasi" autocomplete="off">
                    <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
                </div>
                <div class="chips">
                    <button :class="{ on: phase === 'all' }" @click="phase = 'all'">Semua</button>
                    <button v-for="p in PHASE_ORDER" :key="p" :class="{ on: phase === p }" @click="phase = p">{{ phases[p] }}</button>
                    <span class="sep"></span>
                    <button :class="{ on: onlyOpen }" @click="onlyOpen = !onlyOpen">Belum selesai</button>
                </div>

                <div class="section-title"><span>{{ filtering ? 'Hasil' : 'Kategori' }}</span><span class="num">{{ groups.length }}</span></div>
                <section v-for="g in groups" :key="g.name" class="catgroup" :class="{ open: isOpen(g.name), complete: g.total && g.done === g.total }">
                    <button class="cathead" :aria-expanded="isOpen(g.name)" @click="toggleCat(g.name)">
                        <span class="grow">
                            <b>{{ g.name }}</b>
                            <small class="num">{{ g.done }}/{{ g.total }} selesai<template v-if="g.total - g.done"> · {{ g.total - g.done }} belum</template></small>
                            <span class="catbar"><i :style="{ width: g.pct + '%' }"></i></span>
                        </span>
                        <b class="catpct num">{{ g.pct }}%</b>
                        <Icon v-if="!filtering" name="chevron-right" :size="20" class="catchev" />
                    </button>
                    <ul v-if="isOpen(g.name)" class="preplist">
                        <li v-for="i in g.items" :key="i.id" :class="{ done: i.done }">
                            <button class="ckbtn" :class="{ ro: !canEdit }" :disabled="busyId === i.id || !canEdit" :aria-label="i.done ? 'Batalkan centang' : 'Tandai selesai'" @click="toggle(i)">
                                <Icon v-if="i.done" name="check" :size="20" :stroke="3" />
                            </button>
                            <button class="grow prep-body" :class="{ edit: canEdit }" :disabled="!canEdit" @click="openForm(i)">
                                <b>{{ i.title }}</b>
                                <small v-if="meta(i)">{{ meta(i) }}</small>
                                <small v-if="i.notes" class="prep-notes">{{ i.notes }}</small>
                                <small v-if="i.done && i.done_by" class="prep-done">✓ {{ i.done_by }} · {{ formatDayTime(i.done_at_ms) }}</small>
                            </button>
                        </li>
                    </ul>
                </section>
                <p v-if="!groups.length" class="empty">Tidak ada item yang cocok.</p>
                <p v-if="canEdit" class="note">Ketuk nama item untuk mengubah atau menghapus.</p>
                <button v-if="canEdit" class="btn ghost block" @click="fillTemplate"><Icon name="list" :size="18" /> Tambah item template yang belum ada</button>
            </template>
        </template>

        <!-- Form tambah / ubah (admin) -->
        <template v-if="form">
            <div class="scrim" @click="form = null"></div>
            <section class="sheet prep-form" role="dialog" aria-modal="true" :aria-label="form.id ? 'Ubah item' : 'Tambah item'">
                <div class="sheet-grip"></div>
                <h3>{{ form.id ? 'Ubah item' : 'Tambah item' }}</h3>
                <label class="field"><span>Kategori</span>
                    <div class="input"><input v-model="form.category" list="prep-cats" placeholder="mis. Pos & Water Station" autocomplete="off"></div>
                    <datalist id="prep-cats"><option v-for="c in data?.categories ?? []" :key="c" :value="c"></option></datalist>
                </label>
                <label class="field"><span>Nama item / tugas</span>
                    <div class="input"><input v-model="form.title" placeholder="mis. Air mineral" autocomplete="off"></div>
                </label>
                <div class="two">
                    <label class="field"><span>Jumlah</span><div class="input"><input v-model="form.qty" placeholder="20 dus" autocomplete="off"></div></label>
                    <label class="field"><span>Lokasi</span><div class="input"><input v-model="form.location" placeholder="WS1" autocomplete="off"></div></label>
                </div>
                <label class="field"><span>PIC (penanggung jawab)</span>
                    <div class="input"><input v-model="form.pic" placeholder="Nama" autocomplete="off"></div>
                </label>
                <div class="field"><span>Fase</span>
                    <div class="chips wrap">
                        <button :class="{ on: !form.phase }" @click="form.phase = ''">—</button>
                        <button v-for="p in PHASE_ORDER" :key="p" :class="{ on: form.phase === p }" @click="form.phase = p">{{ phases[p] }}</button>
                    </div>
                </div>
                <label class="field"><span>Catatan</span>
                    <div class="input textarea"><textarea v-model="form.notes" rows="2" placeholder="Opsional"></textarea></div>
                </label>
                <div class="two">
                    <button class="btn" :disabled="saving" @click="form = null">Batal</button>
                    <button class="btn primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
                </div>
                <button v-if="form.id" class="btn ghost block danger-text" :disabled="saving" @click="remove"><Icon name="trash" :size="18" /> Hapus item</button>
            </section>
        </template>
    </main>
</template>
