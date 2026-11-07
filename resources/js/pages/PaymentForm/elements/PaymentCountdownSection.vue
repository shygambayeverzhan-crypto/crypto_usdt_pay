<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
  expiresAt: string | null
}

const props = defineProps<Props>()
const { __ } = vueLang();

function parseIsoToMs(s: string | null): number | null {
  if (!s) return null
  const ms = Date.parse(s)
  return Number.isFinite(ms) ? ms : null
}

const expiresAtMs = computed(() => parseIsoToMs(props.expiresAt))
const remainingSeconds = ref(0)
const hh = computed(() => Math.floor(remainingSeconds.value / 3600))
const mm = computed(() => Math.floor((remainingSeconds.value % 3600) / 60))
const ss = computed(() => remainingSeconds.value % 60)

let countdownTimer: number | null = null

function tickCountdown() {
  if (!expiresAtMs.value) {
    remainingSeconds.value = 0
    return
  }
  const diff = Math.max(0, Math.floor((expiresAtMs.value - Date.now()) / 1000))
  remainingSeconds.value = diff
  if (diff === 0 && countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

function startCountdown() {
  tickCountdown()
  if (countdownTimer) clearInterval(countdownTimer)
  countdownTimer = window.setInterval(tickCountdown, 1000)
}

onMounted(() => {
  startCountdown()
})

onBeforeUnmount(() => {
  if (countdownTimer) clearInterval(countdownTimer)
})
</script>

<template>
  <div class="grid gap-1" v-if="props.expiresAt">
    <div class="text-md opacity-60">{{ __('frontend.payment_form.countdown.title') }}</div>
    <span class="countdown font-mono text-md">
      <span :style="`--value:${hh};`" :aria-label="String(hh)" aria-live="polite">{{ hh }}</span>
      :
      <span :style="`--value:${mm}; --digits: 2;`" :aria-label="String(mm)" aria-live="polite">{{ String(mm).padStart(2,'0') }}</span>
      :
      <span :style="`--value:${ss}; --digits: 2;`" :aria-label="String(ss)" aria-live="polite">{{ String(ss).padStart(2,'0') }}</span>
    </span>
  </div>
</template>

