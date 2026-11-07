<script setup lang="ts">
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
  txid: string | null
  txExplorerUrl?: string | null
}

const props = defineProps<Props>()
const { __ } = vueLang();
</script>

<template>
  <div class="grid gap-2" v-if="props.txid">
    <div class="text-base opacity-60">{{ __('frontend.payment_form.transaction.title') }}</div>
    <div class="flex items-center gap-2">
      <a v-if="props.txExplorerUrl" :href="props.txExplorerUrl || '#'" target="_blank" rel="noopener noreferrer" class="link link-primary">
        {{ __('frontend.payment_form.transaction.explorer') }}
      </a>
    </div>
  </div>
</template>

