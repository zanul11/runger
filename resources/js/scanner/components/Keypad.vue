<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { BIB_MAX_LENGTH } from '../bib.js';
import { unlockAudio } from '../feedback.js';
import Icon from './Icon.vue';

/**
 * Keypad angka besar (v-model = angka yang sedang diketik; tampil di stage induk).
 * ENTER langsung mengirim tanpa konfirmasi. Juga menerima keyboard fisik / scanner barcode.
 */
const model = defineModel({ type: String, default: '' });
const props = defineProps({ enterLabel: { type: String, default: 'OK' } });
const emit = defineEmits(['submit', 'typing']);

/** Getar singkat tiap tombol (seperti keyboard HP). ENTER memakai pola getar hasil scan. */
function tap() {
    try {
        navigator.vibrate?.(12);
    } catch {
        /* iOS tidak punya vibrate */
    }
}

function press(d) {
    unlockAudio();
    tap();
    if (model.value.length < BIB_MAX_LENGTH + 2) model.value += d;
    emit('typing');
}
function back() {
    tap();
    model.value = model.value.slice(0, -1);
}
function clear() {
    model.value = '';
}
function enter() {
    unlockAudio();
    if (model.value === '') return;
    emit('submit', model.value);
    model.value = '';
}

function onKey(e) {
    if (e.target instanceof HTMLInputElement) return;
    if (/^[0-9]$/.test(e.key)) press(e.key);
    else if (e.key === 'Backspace') back();
    else if (e.key === 'Escape') clear();
    else if (e.key === 'Enter') enter();
    else return;
    e.preventDefault();
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="keys" role="group" aria-label="Keypad nomor BIB">
        <button v-for="d in ['1', '2', '3', '4', '5', '6', '7', '8', '9']" :key="d" class="key num" @click="press(d)">{{ d }}</button>
        <button class="key fn" aria-label="Hapus (tahan untuk kosongkan)" @click="back" @contextmenu.prevent="clear" @dblclick="clear">
            <Icon name="backspace" :size="30" />
        </button>
        <button class="key num" @click="press('0')">0</button>
        <button class="key enter" :disabled="!model" :aria-label="props.enterLabel" @click="enter">
            <Icon name="check" :size="34" :stroke="3" />
        </button>
    </div>
</template>
