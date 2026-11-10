<script setup>
import { state } from './store.js';
import LoginView from './components/LoginView.vue';
import MainView from './components/MainView.vue';
</script>

<template>
    <LoginView v-if="!state.session || state.authExpired" />
    <MainView v-else />
</template>
