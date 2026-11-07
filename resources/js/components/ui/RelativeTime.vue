<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { vueLang } from '@erag/lang-sync-inertia'

interface Props {
  /**
   * Дата/время в ISO-формате (или 'YYYY-MM-DD HH:mm:ss')
   */
  value: string
}

const props = defineProps<Props>()

type Unit = 'second' | 'minute' | 'hour' | 'day'

const page = usePage()
const { __ } = vueLang()

const now = ref<number>(Date.now())

const locale = computed(() => (page.props as any)?.locale ?? 'en')

const dateObject = computed<Date | null>(() => parseDate(props.value))

const diffSeconds = computed<number>(() => {
  if (!dateObject.value) return 0
  const diff = (now.value - dateObject.value.getTime()) / 1000
  return Number.isFinite(diff) ? Math.max(0, diff) : 0
})

const display = computed<string>(() => {
  if (!dateObject.value) return ''
  return formatRelative(diffSeconds.value)
})

function parseDate(value: string): Date | null {
  if (!value) return null
  const prepared = value.includes('T') ? value : `${value.replace(' ', 'T')}Z`
  const parsed = new Date(prepared)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function formatRelative(seconds: number): string {
  const rounded = Math.round(seconds)
  if (rounded <= 0) return __('frontend.time_ago.just_now')

  if (rounded < 60) {
    return translate('second', Math.max(1, rounded))
  }

  const minutes = Math.round(rounded / 60)
  if (minutes < 60) {
    return translate('minute', Math.max(1, minutes))
  }

  const hours = Math.round(rounded / 3600)
  if (hours < 24) {
    return translate('hour', Math.max(1, hours))
  }

  const days = Math.round(rounded / 86400)
  return translate('day', Math.max(1, days))
}

function translate(unit: Unit, count: number): string {
  const form = locale.value === 'ru'
    ? selectRuForm(count)
    : count === 1
      ? 'one'
      : 'many'

  return __(`frontend.time_ago.${unit}.${form}`, { count })
}

function selectRuForm(count: number): 'one' | 'few' | 'many' {
  const mod10 = count % 10
  const mod100 = count % 100

  if (mod10 === 1 && mod100 !== 11) return 'one'
  if (mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14)) return 'few'
  return 'many'
}

watch(() => props.value, () => {
  now.value = Date.now()
})
</script>

<template>
  <span :title="dateObject ? dateObject.toISOString() : ''">
    {{ display }}
  </span>
</template>

