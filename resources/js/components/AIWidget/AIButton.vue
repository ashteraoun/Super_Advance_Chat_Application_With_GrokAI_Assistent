<template>
  <div>
    <button
      class="ai-widget-button"
      @click="toggle"
      :aria-expanded="open"
      :title="open ? 'Close AI Assistant' : 'Open AI Assistant'"
    >
      <svg class="ai-icon ai-icon-chat" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2C6.48 2 2 5.94 2 10.8c0 2.62 1.32 4.96 3.4 6.56-.15 1.24-.6 2.66-1.4 3.64 1.6.06 3.24-.42 4.6-1.28 1.06.32 2.2.48 3.4.48 5.52 0 10-3.94 10-8.8S17.52 2 12 2z" fill="currentColor"/>
        <circle cx="8.2" cy="10.8" r="1.15" fill="var(--ai-accent-1, #6366f1)"/>
        <circle cx="12" cy="10.8" r="1.15" fill="var(--ai-accent-1, #6366f1)"/>
        <circle cx="15.8" cy="10.8" r="1.15" fill="var(--ai-accent-1, #6366f1)"/>
      </svg>
      <svg class="ai-icon ai-icon-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
      </svg>
      <span v-if="!open && hasUnread" class="ai-widget-badge"></span>
    </button>
    <AIWindow v-if="mounted" :open="open" @close="close" @minimize="minimize" @message-received="onMessageReceived" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AIWindow from './AIWindow.vue';

const open = ref(false);
const mounted = ref(false);
const hasUnread = ref(false);

function toggle() {
  open.value = !open.value;
  mounted.value = true;
  if (open.value) hasUnread.value = false;
}
function close() { open.value = false; }
function minimize() { open.value = false; }
function onMessageReceived() {
  if (!open.value) hasUnread.value = true;
}

onMounted(() => { mounted.value = true; });
</script>

<style scoped>
.ai-widget-button { backdrop-filter: blur(6px); }
</style>
