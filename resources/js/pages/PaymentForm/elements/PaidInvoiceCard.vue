<script setup lang="ts">
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
  txExplorerUrl?: string | null
}

const props = defineProps<Props>()
const { __ } = vueLang();
</script>

<template>
  <div class="card bg-base-100 shadow">
    <div class="card-body p-6 lg:p-10">
      <div class="flex flex-col items-center text-center gap-2">
        <div class="text-success">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-32">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
        </div>
        <div class="text-2xl font-semibold">{{ __('frontend.payment_form.cards.paid_title') }}</div>
        <div class="flex items-center gap-2" v-if="props.txExplorerUrl">
          <a :href="props.txExplorerUrl" target="_blank" rel="noopener noreferrer" class="link link-primary inline-flex items-center gap-1">
            <span>{{ __('frontend.payment_form.cards.open_explorer') }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

