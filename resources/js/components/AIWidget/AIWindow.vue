<template>
  <div :class="['ai-widget-panel', open ? '' : 'hidden']" @keydown.esc="close">
    <div class="ai-widget-header">
      <div class="ai-avatar">✨</div>
      <div class="ai-title-block">
        <div class="title">AI Assistant</div>
        <div class="subtitle"><span class="status-dot"></span> Online</div>
      </div>
      <div class="ai-header-actions">
        <button @click="clearChat" title="New chat">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 12a8 8 0 1 1 2.34 5.66" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20v-5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button @click="minimize" title="Minimize">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        </button>
        <button @click="close" title="Close">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        </button>
      </div>
    </div>

    <div class="ai-widget-body" ref="body">
      <div v-if="messages.length === 0" class="ai-empty-state">
        <div class="ai-empty-icon">✨</div>
        <h3>Hey there 👋</h3>
        <p>Ask me anything — I can help answer questions, explain things, or just chat.</p>
        <div class="ai-suggestions">
          <button
            v-for="(s, i) in suggestions"
            :key="i"
            class="ai-suggestion-chip"
            @click="sendSuggestion(s)"
          >{{ s }}</button>
        </div>
      </div>

      <AIMessage v-for="(m, idx) in messages" :key="idx" :message="m" />

      <div v-if="loading" class="ai-msg-row ai">
        <div class="ai-msg-avatar ai">✨</div>
        <div class="ai-typing"><span></span><span></span><span></span></div>
      </div>
    </div>

    <div class="ai-widget-footer">
      <div class="ai-input-wrap">
        <textarea
          ref="textareaRef"
          v-model="input"
          @input="autoResize"
          @keydown.enter.exact.prevent="send"
          @keydown.enter.shift.prevent="newline"
          class="ai-input"
          rows="1"
          :placeholder="placeholder"
        ></textarea>
      </div>
      <button class="ai-send-btn" :disabled="!canSend || loading" @click="send" title="Send">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 12l16-8-6 8 6 8-16-8z" fill="currentColor"/></svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick, computed } from 'vue';
import AIMessage from './AIMessage.vue';
import axios from 'axios';

const props = defineProps({ open: Boolean });
const emit = defineEmits(['close', 'minimize', 'message-received']);

const input = ref('');
const messages = ref([]);
const loading = ref(false);
const placeholder = 'Ask the AI assistant...';
const textareaRef = ref(null);
const body = ref(null);

const suggestions = [
  'What can you help me with?',
  'Summarize my recent chats',
  'Give me a fun fact',
];

const canSend = computed(() => input.value.trim().length > 0);

function newline() { input.value += '\n'; autoResize(); }

function autoResize() {
  const el = textareaRef.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function sendSuggestion(text) {
  input.value = text;
  send();
}

async function send() {
  if (!canSend.value || loading.value) return;
  const text = input.value.trim();
  messages.value.push({ role: 'user', content: text, time: new Date().toLocaleTimeString() });
  input.value = '';
  await nextTick();
  autoResize();
  scrollToBottom();
  loading.value = true;

  try {
    const history = messages.value.map(m => ({ role: m.role, content: m.content }));
    const res = await axios.post('/api/ai/chat', { message: text, history });
    if (res.data && res.data.success) {
      messages.value.push({ role: 'ai', content: res.data.message, time: new Date().toLocaleTimeString() });
    } else {
      messages.value.push({ role: 'ai', content: res.data?.message || 'AI Assistant is temporarily unavailable. Please try again later.', time: new Date().toLocaleTimeString(), error: true });
    }
    emit('message-received');
  } catch (e) {
    const msg = e?.response?.data?.message || 'AI Assistant is temporarily unavailable. Please try again later.';
    messages.value.push({ role: 'ai', content: msg, time: new Date().toLocaleTimeString(), error: true });
  } finally {
    loading.value = false;
    await nextTick();
    scrollToBottom();
  }
}

function scrollToBottom() {
  if (body.value) body.value.scrollTop = body.value.scrollHeight;
}

function clearChat() {
  messages.value = [];
  input.value = '';
}

function close() { emit('close'); }
function minimize() { emit('minimize'); }

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    await nextTick();
    textareaRef.value?.focus();
    scrollToBottom();
  }
});

onMounted(() => { /* nothing for now */ });
</script>

<style scoped>
button { cursor: pointer; }
</style>
