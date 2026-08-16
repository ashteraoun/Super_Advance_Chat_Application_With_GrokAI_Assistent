<template>
  <div :class="['ai-msg-row', message.role === 'user' ? 'user' : 'ai']">
    <div :class="['ai-msg-avatar', message.role === 'user' ? 'user' : 'ai']">
      <span v-if="message.role === 'user'">🧑</span>
      <span v-else>✨</span>
    </div>
    <div class="ai-msg-col">
      <div :class="['ai-msg', message.role === 'user' ? 'user' : (message.error ? 'error' : 'ai')]" v-html="formattedText"></div>
      <div class="ai-msg-time">{{ message.time }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({ message: Object });

const formattedText = computed(() => {
  const raw = props.message?.content || '';
  const escaped = escapeHtml(raw);

  // Extract fenced code blocks first so inner content isn't touched by other rules
  const blocks = [];
  let withPlaceholders = escaped.replace(/```([\s\S]*?)```/g, (m, p1) => {
    const idx = blocks.length;
    blocks.push(`<pre><code>${p1.trim()}</code></pre>`);
    return `%%CODEBLOCK_${idx}%%`;
  });

  withPlaceholders = withPlaceholders
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br/>');

  blocks.forEach((block, idx) => {
    withPlaceholders = withPlaceholders.replace(`%%CODEBLOCK_${idx}%%`, block);
  });

  return withPlaceholders;
});

function escapeHtml(s) {
  return s.replace(/[&<>'"]/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
  }[c]));
}
</script>
