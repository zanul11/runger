<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { api, apiBlob } from '../api.js';
import { state } from '../store.js';
import { formatDayTime } from '../clock.js';
import { usePoll } from '../admin.js';
import Icon from './Icon.vue';

/**
 * Pengeluaran panitia GTR: role bendahara (layar utama) & admin (dari menu).
 * Tiap pengeluaran: tanggal, nominal, PIC, keterangan + foto / PDF kwitansi.
 * Butuh sinyal (tidak diantre offline) — kwitansi langsung terunggah ke server.
 */
// standalone = layar utama (akun bendahara): tanpa tombol kembali.
const props = defineProps({ standalone: { type: Boolean, default: false } });
const emit = defineEmits(['back', 'notify']);
const token = () => state.session?.token;
const expApi = (path = '', opts = {}) => api('/expenses' + path, { token: token(), timeout: 60000, ...opts });
const { data, error, loading, load } = usePoll(() => expApi(), 60_000);

const rupiah = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID');
const fmtDate = (d) => new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
const today = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60_000).toISOString().slice(0, 10);

// ---- daftar & filter ----
const q = ref('');
const pic = ref('');
const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    return (data.value?.items ?? []).filter((e) => (!pic.value || e.pic === pic.value)
        && (!term || [e.description, e.pic, String(e.amount)].some((v) => v?.toLowerCase().includes(term))));
});
const filteredTotal = computed(() => filtered.value.reduce((s, e) => s + e.amount, 0));
const filtering = computed(() => !!q.value.trim() || !!pic.value);
// Dikelompokkan per tanggal (terbaru di atas), subtotal per hari.
const days = computed(() => {
    const map = new Map();
    for (const e of filtered.value) {
        if (!map.has(e.spent_on)) map.set(e.spent_on, { date: e.spent_on, items: [], total: 0 });
        const d = map.get(e.spent_on);
        d.items.push(e);
        d.total += e.amount;
    }
    return [...map.values()];
});

// ---- form tambah / ubah ----
const form = ref(null);   // null | { id?, spent_on, amount, pic, description, file, preview, had_receipt, remove_receipt }
const saving = ref(false);
const fileInput = ref(null);

function openForm(e = null) {
    clearPreview();
    form.value = e
        ? { id: e.id, spent_on: e.spent_on, amount: String(e.amount), pic: e.pic, description: e.description, file: null, preview: null, had_receipt: e.has_receipt, receipt_pdf: e.receipt_pdf, remove_receipt: false }
        : { spent_on: today(), amount: '', pic: '', description: '', file: null, preview: null, had_receipt: false, receipt_pdf: false, remove_receipt: false };
}

function closeForm() {
    clearPreview();
    form.value = null;
}

function clearPreview() {
    if (form.value?.preview) URL.revokeObjectURL(form.value.preview);
}

// Nominal diketik bebas ("150.000", "150000") → angka saja; ditampilkan dengan titik ribuan.
const amountDisplay = computed({
    get: () => (form.value?.amount ? Number(form.value.amount).toLocaleString('id-ID') : ''),
    set: (v) => { form.value.amount = String(v).replace(/\D/g, '').replace(/^0+/, '').slice(0, 13); },
});

/** Foto HP bisa > 5 MB → kecilkan ke maks. 1600 px JPEG sebelum diunggah (PDF dikirim apa adanya). */
async function shrink(file) {
    if (!file.type.startsWith('image/')) return file;
    try {
        const bmp = await createImageBitmap(file);
        const scale = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
        if (scale === 1 && file.size < 1.5 * 1024 * 1024 && file.type === 'image/jpeg') return file;
        const c = document.createElement('canvas');
        c.width = Math.round(bmp.width * scale);
        c.height = Math.round(bmp.height * scale);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.85));
        return blob ? new File([blob], 'kwitansi.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        return file;   // browser tidak bisa membaca (mis. HEIC di Android) → kirim aslinya
    }
}

async function pickFile(ev) {
    const f = ev.target.files?.[0];
    ev.target.value = '';
    if (!f) return;
    if (!/^image\/|^application\/pdf$/.test(f.type)) {
        emit('notify', 'Kwitansi harus foto atau PDF.');
        return;
    }
    const file = await shrink(f);
    if (file.size > 10 * 1024 * 1024) {
        emit('notify', 'Ukuran kwitansi maksimal 10 MB.');
        return;
    }
    clearPreview();
    form.value.file = file;
    form.value.preview = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    form.value.remove_receipt = false;
}

function dropFile() {
    clearPreview();
    form.value.file = null;
    form.value.preview = null;
    if (form.value.had_receipt) form.value.remove_receipt = true;
}

async function save() {
    const f = form.value;
    if (!f.spent_on || !Number(f.amount) || !f.pic.trim() || !f.description.trim()) {
        emit('notify', 'Tanggal, nominal, PIC & keterangan wajib diisi.');
        return;
    }
    const body = new FormData();
    body.append('spent_on', f.spent_on);
    body.append('amount', f.amount);
    body.append('pic', f.pic.trim());
    body.append('description', f.description.trim());
    if (f.file) body.append('receipt', f.file, f.file.name || 'kwitansi');
    if (f.remove_receipt) body.append('remove_receipt', '1');
    saving.value = true;
    try {
        await expApi(f.id ? `/${f.id}` : '', { method: 'POST', body });
        emit('notify', f.id ? 'Pengeluaran diperbarui.' : 'Pengeluaran disimpan.');
        closeForm();
        await load();
    } catch (e) {
        emit('notify', state.online ? (e.message || 'Gagal menyimpan') : 'Offline — simpan pengeluaran butuh sinyal');
    } finally {
        saving.value = false;
    }
}

async function remove() {
    if (!confirm(`Hapus pengeluaran "${form.value.description}" (${rupiah(form.value.amount)}) beserta kwitansinya?`)) return;
    saving.value = true;
    try {
        await expApi(`/${form.value.id}`, { method: 'DELETE' });
        emit('notify', 'Pengeluaran dihapus.');
        closeForm();
        await load();
    } catch (e) {
        emit('notify', e.message || 'Gagal menghapus');
    } finally {
        saving.value = false;
    }
}

// ---- lihat kwitansi (file privat → diambil dengan token) ----
const viewer = ref(null);   // null | { url, pdf, title }
async function openReceipt(e) {
    try {
        const blob = await apiBlob(`/expenses/${e.id}/receipt`, { token: token() });
        const url = URL.createObjectURL(blob);
        if (blob.type === 'application/pdf') {
            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 60_000);
            return;
        }
        viewer.value = { url, title: `${e.description} · ${rupiah(e.amount)}` };
    } catch (err) {
        emit('notify', err.message || 'Gagal membuka kwitansi');
    }
}
function closeViewer() {
    if (viewer.value) URL.revokeObjectURL(viewer.value.url);
    viewer.value = null;
}
onBeforeUnmount(() => { closeViewer(); clearPreview(); });

// ---- export CSV (dibuka di Excel / dibagikan ke WA) ----
async function exportCsv() {
    try {
        const blob = await apiBlob('/expenses/export', { token: token() });
        const name = `pengeluaran-gtr-${today()}.csv`;
        const file = new File([blob], name, { type: 'text/csv' });
        if (navigator.canShare?.({ files: [file] })) {
            await navigator.share({ files: [file], title: 'Pengeluaran GTR 2026' });
            return;
        }
        const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: name });
        a.click();
        setTimeout(() => URL.revokeObjectURL(a.href), 10_000);
    } catch (e) {
        if (e?.name !== 'AbortError') emit('notify', e.message || 'Gagal export');
    }
}
</script>

<template>
    <main class="panel admin">
        <div class="viewbar">
            <button v-if="!props.standalone" class="btn sm icon-only" aria-label="Kembali" @click="emit('back')"><Icon name="chevron-left" :size="22" :stroke="2.75" /></button>
            <h2>Pengeluaran</h2>
            <button class="btn sm icon-only ghost" :disabled="loading" aria-label="Muat ulang" @click="load"><Icon name="refresh" :size="20" /></button>
        </div>
        <div v-if="error" class="alert warn"><Icon name="wifi-off" :size="20" /><span>{{ error }}</span></div>

        <template v-if="data">
            <section class="acard exp-sum">
                <div>
                    <small>Total pengeluaran</small>
                    <b class="num">{{ rupiah(data.total) }}</b>
                    <span class="note">{{ data.count }} transaksi</span>
                </div>
                <button class="btn primary block" @click="openForm()"><Icon name="plus-square" :size="20" /> Tambah pengeluaran</button>
                <button v-if="data.count" class="btn block ghost" @click="exportCsv"><Icon name="download" :size="18" /> Export (CSV / Excel)</button>
            </section>

            <p v-if="!data.count" class="empty">Belum ada pengeluaran. Ketuk "Tambah pengeluaran" untuk mencatat yang pertama.</p>

            <template v-else>
                <div class="input search sticky">
                    <Icon name="search" :size="20" />
                    <input v-model="q" type="search" inputmode="search" placeholder="Cari keterangan, PIC, nominal" autocomplete="off">
                    <button v-if="q" class="eye" aria-label="Hapus pencarian" @click="q = ''"><Icon name="x" :size="20" /></button>
                </div>
                <div v-if="data.pics.length > 1" class="chips">
                    <button :class="{ on: !pic }" @click="pic = ''">Semua PIC</button>
                    <button v-for="p in data.pics" :key="p" :class="{ on: pic === p }" @click="pic = pic === p ? '' : p">{{ p }}</button>
                </div>
                <p v-if="filtering" class="note exp-filter">{{ filtered.length }} transaksi · <b class="num">{{ rupiah(filteredTotal) }}</b></p>

                <section v-for="d in days" :key="d.date" class="exp-day">
                    <div class="section-title"><span>{{ fmtDate(d.date) }}</span><span class="num">{{ rupiah(d.total) }}</span></div>
                    <ul class="preplist">
                        <li v-for="e in d.items" :key="e.id" class="exp-item">
                            <button class="grow prep-body edit" @click="openForm(e)">
                                <b>{{ e.description }}</b>
                                <small>PIC {{ e.pic }}</small>
                                <small class="exp-by">Dicatat {{ e.created_by ?? '—' }} · {{ formatDayTime(e.created_at_ms) }}<template v-if="e.updated_by"> · diubah {{ e.updated_by }}</template></small>
                            </button>
                            <div class="exp-right">
                                <b class="num">{{ rupiah(e.amount) }}</b>
                                <button v-if="e.has_receipt" class="exp-rc" @click="openReceipt(e)"><Icon :name="e.receipt_pdf ? 'list' : 'eye'" :size="15" /> Kwitansi</button>
                                <span v-else class="exp-rc none">Tanpa kwitansi</span>
                            </div>
                        </li>
                    </ul>
                </section>
                <p v-if="!days.length" class="empty">Tidak ada pengeluaran yang cocok.</p>
                <p class="note">Ketuk keterangan untuk mengubah atau menghapus.</p>
            </template>
        </template>

        <!-- Form tambah / ubah -->
        <template v-if="form">
            <div class="scrim" @click="closeForm"></div>
            <section class="sheet prep-form" role="dialog" aria-modal="true" :aria-label="form.id ? 'Ubah pengeluaran' : 'Tambah pengeluaran'">
                <div class="sheet-grip"></div>
                <h3>{{ form.id ? 'Ubah pengeluaran' : 'Tambah pengeluaran' }}</h3>
                <div class="two">
                    <label class="field"><span>Tanggal</span>
                        <div class="input"><input v-model="form.spent_on" type="date" :max="today()"></div>
                    </label>
                    <label class="field"><span>Nominal (Rp)</span>
                        <div class="input"><input v-model="amountDisplay" inputmode="numeric" placeholder="150.000" autocomplete="off"></div>
                    </label>
                </div>
                <label class="field"><span>PIC (penanggung jawab)</span>
                    <div class="input"><input v-model="form.pic" placeholder="Nama yang membelanjakan" maxlength="120" autocomplete="off" list="exp-pics"></div>
                    <datalist id="exp-pics"><option v-for="p in data?.pics ?? []" :key="p" :value="p" /></datalist>
                </label>
                <div v-if="data?.pics?.length" class="chips wrap">
                    <button v-for="p in data.pics.slice(0, 8)" :key="p" type="button" :class="{ on: form.pic === p }" @click="form.pic = p">{{ p }}</button>
                </div>
                <label class="field"><span>Keterangan</span>
                    <div class="input textarea"><textarea v-model="form.description" rows="3" maxlength="2000" placeholder="mis. Beli air mineral 20 dus untuk WS1"></textarea></div>
                </label>

                <div class="field"><span>Kwitansi / nota</span>
                    <div v-if="form.file" class="exp-file">
                        <img v-if="form.preview" :src="form.preview" alt="Pratinjau kwitansi">
                        <span v-else class="exp-pdf"><Icon name="list" :size="22" /> {{ form.file.name }}</span>
                        <button class="btn sm ghost danger-text" type="button" @click="dropFile"><Icon name="trash" :size="16" /> Hapus</button>
                    </div>
                    <div v-else-if="form.had_receipt && !form.remove_receipt" class="exp-file">
                        <span class="exp-pdf"><Icon name="check" :size="20" /> Kwitansi sudah terunggah</span>
                        <button class="btn sm ghost" type="button" @click="openReceipt(form)">Lihat</button>
                        <button class="btn sm ghost danger-text" type="button" @click="dropFile"><Icon name="trash" :size="16" /></button>
                    </div>
                    <p v-if="form.remove_receipt" class="note">Kwitansi lama akan dihapus saat disimpan.</p>
                    <input ref="fileInput" type="file" accept="image/*,application/pdf" hidden @change="pickFile">
                    <button class="btn block" type="button" @click="fileInput.click()"><Icon name="camera" :size="18" /> {{ form.file || (form.had_receipt && !form.remove_receipt) ? 'Ganti foto / file' : 'Foto / pilih kwitansi' }}</button>
                </div>

                <div class="two">
                    <button class="btn" :disabled="saving" @click="closeForm">Batal</button>
                    <button class="btn primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
                </div>
                <button v-if="form.id" class="btn ghost block danger-text" :disabled="saving" @click="remove"><Icon name="trash" :size="18" /> Hapus pengeluaran</button>
            </section>
        </template>

        <!-- Lihat kwitansi (foto) -->
        <template v-if="viewer">
            <div class="scrim" @click="closeViewer"></div>
            <section class="sheet exp-viewer" role="dialog" aria-modal="true" aria-label="Kwitansi">
                <div class="sheet-grip"></div>
                <p class="note">{{ viewer.title }}</p>
                <img :src="viewer.url" alt="Kwitansi">
                <div class="two">
                    <a class="btn" :href="viewer.url" download="kwitansi.jpg"><Icon name="download" :size="18" /> Simpan</a>
                    <button class="btn primary" @click="closeViewer">Tutup</button>
                </div>
            </section>
        </template>
    </main>
</template>
