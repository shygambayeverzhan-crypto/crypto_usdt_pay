<script setup lang="ts">
import { vueLang } from '@erag/lang-sync-inertia';

const { __ } = vueLang();
</script>

<template>
  <div class="card bg-base-100 shadow">
    <div class="card-body p-6 lg:p-10">
      <div class="flex flex-col items-center text-center gap-2">
        <div class="text-error">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-32">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
        </div>
        <div class="text-2xl font-semibold">{{ __('frontend.payment_form.cards.expired_title') }}</div>
      </div>
    </div>
  </div>
</template>

