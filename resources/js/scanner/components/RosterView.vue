<script setup>
import { defineAsyncComponent, onBeforeUnmount, onMounted } from 'vue';
import { api } from '../api.js';
import { state } from '../store.js';

/**
 * GTR Crew → Roster panitia hari H (layar penuh, tema hijau hutan). Semua akun boleh melihat;
 * panitia (koordinator) & admin mengisi petugas. Komponen tampilan dipakai bersama halaman
 * publik baca-saja /crew/roster (resources/js/roster/RosterBoard.vue).
 */
const RosterBoard = defineAsyncComponent(() => import('../../roster/RosterBoard.vue'));
const emit = defineEmits(['back']);

const roster = (path = '', opts = {}) => api('/roster' + path, { token: state.session?.token, timeout: 15000, ...opts });
const load = () => roster();
const saveSlot = async (pid, no, fields) => (await roster(`/positions/${pid}/slots/${no}`, { method: 'PUT', body: fields })).slot;
const saveTamu = (n) => roster('/consumption', { method: 'PUT', body: { tamu_tambahan: n } });
const exportJson = () => roster('/export');
const importJson = (mode, data, preview) => roster(preview ? '/import/preview' : '/import', { method: 'POST', body: { mode, data }, timeout: 60000 });
const updateItem = (type, id, body) => roster(`/${type}/${id}`, { method: 'PATCH', body });
const deleteItem = (type, id) => roster(`/${type}/${id}`, { method: 'DELETE' });
const shareUrl = `${location.origin}/crew/roster`;

// Layar di belakang tidak ikut scroll.
onMounted(() => document.documentElement.classList.add('roster-open'));
onBeforeUnmount(() => document.documentElement.classList.remove('roster-open'));
</script>

<template>
    <div class="roster-layer">
        <RosterBoard :load="load" :save-slot="saveSlot" :save-tamu="saveTamu" :export-json="exportJson" :import-json="importJson" :update-item="updateItem" :delete-item="deleteItem"
                     :share-url="shareUrl" show-back @back="emit('back')" />
    </div>
</template>
