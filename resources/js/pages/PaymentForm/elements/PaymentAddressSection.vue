<script setup lang="ts">
import { ref } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
  address: string
}

const props = defineProps<Props>()
const { __ } = vueLang()

const tooltipText = ref(__('frontend.payment_form.address.copy'))
let resetTimer: number | undefined

async function copyAddress() {
  try {
    await navigator.clipboard.writeText(props.address)
    tooltipText.value = __('frontend.payment_form.address.copied')
  } catch (_) {
    tooltipText.value = __('frontend.payment_form.address.copy_failed')
  } finally {
    if (resetTimer) clearTimeout(resetTimer)
    resetTimer = window.setTimeout(() => {
      tooltipText.value = __('frontend.payment_form.address.copy')
    }, 1500)
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    copyAddress()
  }
}
</script>

<template>
  <div class="grid gap-1">
    <div class="text-md opacity-60">{{ __('frontend.payment_form.address.title') }}</div>
    <div class="font-mono">
      <div class="tooltip" :data-tip="tooltipText">
        <div
          class="group text-md min-h-0 flex items-center justify-between gap-2 font-mono cursor-pointer transition-colors"
          tabindex="0"
          @click="copyAddress"
          @keydown="onKeydown"
        >
          <span class="break-all">{{ props.address }}</span>
          <span class="shrink-0 text-info opacity-70 group-hover:opacity-100 transition-opacity" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
            </svg>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

