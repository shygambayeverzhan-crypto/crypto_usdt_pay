<script setup lang="ts">
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
  qrUrl: string
  currencyLabel?: string | null
  currency: string
  networkLabel?: string | null
  network: string
  address?: string | null
}

const props = defineProps<Props>()
const { __ } = vueLang()
</script>

<template>
  <div>
    <div class="mb-2">
      <div class="text-xs text-center opacity-70 mt-1">
        {{ __('frontend.payment_form.qr.currency_network', { currency: props.currencyLabel || props.currency, network: props.networkLabel || props.network }) }}
      </div>
    </div>
    <div class="mx-auto rounded-box aspect-square w-40 sm:w-50 max-w-full overflow-hidden">
      <img
        v-if="props.address"
        :src="props.qrUrl"
        :alt="__('frontend.payment_form.qr.alt')"
        class="w-full h-full object-contain"
        loading="eager"
        decoding="async"
      />
    </div>
  </div>
</template>

