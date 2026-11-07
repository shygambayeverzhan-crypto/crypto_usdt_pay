<script setup lang="ts">
import { computed, ref } from 'vue'
import CurrencyNetworkBadge from '@/components/ui/CurrencyNetworkBadge.vue'

export type CurrencyNetworkOption = {
  value: string
  currency: string
  currency_label: string
  network: string
  network_label: string
  disabled?: boolean
}

interface Props {
  id?: string
  modelValue: string | null
  options: CurrencyNetworkOption[]
  placeholder?: string
  disabled?: boolean
  emptyLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  options: () => [],
  placeholder: '',
  disabled: false,
  emptyLabel: '',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const isOpen = ref(false)

const selected = computed(() =>
  props.options.find((opt) => String(opt.value) === String(props.modelValue ?? '')),
)

function toggle() {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

function close() {
  isOpen.value = false
}

function selectOption(option: CurrencyNetworkOption) {
  if (option.disabled) return
  emit('update:modelValue', option.value)
  close()
}

function handleBlur(event: FocusEvent) {
  const current = event.currentTarget as HTMLElement | null
  const related = event.relatedTarget as HTMLElement | null
  if (!current) return
  if (!related || !current.contains(related)) {
    close()
  }
}
</script>

<template>
  <div class="dropdown w-full" :class="{ 'dropdown-open': isOpen }" @focusout="handleBlur">
    <button
      type="button"
      class="btn btn-outline border border-base-300 w-full justify-between"
      :id="id"
      :class="{ 'opacity-60': disabled }"
      :disabled="disabled"
      @click="toggle"
      @keydown.enter.prevent="toggle"
      @keydown.space.prevent="toggle"
    >
      <div class="flex items-center gap-3 min-w-0">
        <div v-if="selected" class="min-w-0 truncate">
          <CurrencyNetworkBadge
            :icon-size="22"
            :currency="selected.currency"
            :currency-label="selected.currency_label || selected.currency"
            :network="selected.network"
            :network-label="selected.network_label || selected.network"
          />
        </div>
        <div v-else class="flex-1 text-left truncate opacity-60">
          {{ placeholder }}
        </div>
      </div>
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
      </svg>
    </button>

    <ul class="dropdown-content menu bg-base-100 rounded-box shadow w-full mt-2 max-h-72 overflow-auto">
      <li v-if="!options.length" class="px-4 py-3 text-sm opacity-70">
        {{ emptyLabel || placeholder }}
      </li>
      <li v-for="option in options" :key="option.value">
        <button
          type="button"
          class="flex items-center gap-3 px-3 py-2 w-full text-left"
          :class="{ 'opacity-60': option.disabled }"
          :disabled="option.disabled"
          @click="selectOption(option)"
        >
          <div class="flex-1 min-w-0">
            <CurrencyNetworkBadge
              :icon-size="22"
              :currency="option.currency"
              :currency-label="option.currency_label || option.currency"
              :network="option.network"
              :network-label="option.network_label || option.network"
            />
          </div>
          <div v-if="selected && String(selected.value) === String(option.value)" class="text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
            </svg>
          </div>
        </button>
      </li>
    </ul>
  </div>
</template>


