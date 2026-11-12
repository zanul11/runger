<script setup>
import { computed } from 'vue';
import { install, promptInstall, dismissInstall } from '../install.js';
import Icon from './Icon.vue';

/**
 * Ajakan memasang scanner sebagai aplikasi (PWA).
 *  - banner: satu baris; hanya tampil bila dialog pasang siap / di iOS, dan bisa ditutup.
 *  - selain itu (login, menu): selalu tampil selama belum terpasang, termasuk petunjuk manual.
 */
const props = defineProps({ banner: { type: Boolean, default: false } });
const emit = defineEmits(['notify']);

const mode = computed(() => (install.canPrompt ? 'prompt' : install.ios ? 'ios' : 'manual'));
const show = computed(() => {
    if (install.installed) return false;
    if (!props.banner) return true;
    return !install.dismissed && mode.value !== 'manual';
});

async function add() {
    if (await promptInstall()) emit('notify', 'Scanner terpasang — buka dari layar utama HP.');
}
</script>

<template>
    <!-- Banner di layar utama: satu baris saja supaya keypad tidak terdorong ke bawah. -->
    <section v-if="show && props.banner" class="install-bar" aria-label="Pasang aplikasi">
        <Icon name="download" :size="22" />
        <span v-if="mode === 'prompt'"><b>Pasang aplikasi</b> di HP ini</span>
        <span v-else><b>Pasang:</b> ketuk <Icon name="share" :size="16" /> Bagikan → Tambah ke Layar Utama</span>
        <button v-if="mode === 'prompt'" type="button" class="btn primary sm" @click="add">Pasang</button>
        <button type="button" class="close" aria-label="Tutup ajakan pasang" @click="dismissInstall"><Icon name="x" :size="20" /></button>
    </section>

    <section v-else-if="show" class="install" aria-label="Pasang aplikasi">
        <div class="install-icon"><Icon name="download" :size="24" /></div>
        <b>Pasang GTR Scanner di HP</b>
        <p v-if="mode === 'prompt'">Buka lebih cepat dari layar utama, layar penuh, dan tetap jalan tanpa sinyal.</p>
        <p v-else-if="mode === 'ios'">Di iPhone/iPad, pasang lewat menu Bagikan:</p>
        <p v-else>Pasang lewat menu browser:</p>

        <ol v-if="mode === 'ios'">
            <li>Ketuk <Icon name="share" :size="18" /> <b>Bagikan</b> di bilah browser</li>
            <li>Pilih <Icon name="plus-square" :size="18" /> <b>Tambah ke Layar Utama</b></li>
            <li>Ketuk <b>Tambah</b></li>
        </ol>
        <ol v-else-if="mode === 'manual'">
            <li>Buka menu browser <Icon name="more" :size="18" /></li>
            <li>Pilih <b>Pasang aplikasi</b> / <b>Tambahkan ke layar utama</b></li>
        </ol>

        <div v-if="mode === 'prompt'" class="install-actions">
            <button type="button" class="btn primary sm" @click="add"><Icon name="download" :size="20" /> Pasang aplikasi</button>
        </div>
    </section>
</template>
