<script setup lang="ts">
import { computed, ref, onUnmounted, watch } from 'vue'
import { vueLang } from '@erag/lang-sync-inertia'

interface Props {
  /**
   * ISO-строка даты (например: "2025-11-01T12:34:56Z")
   */
  value: string
  /**
   * Поддерживаемые токены: YYYY (или YY при shortYear), MM, DD, HH, mm, ss
   * По умолчанию: 'YYYY.MM.DD HH:mm:ss' (при shortYear будет отображаться как YY.MM.DD HH:mm:ss)
   */
  pattern?: string
  /**
   * Отображать год в сокращенном формате (2025 → 25)
   * По умолчанию: true
   */
  shortYear?: boolean
  /**
   * Скрывать секунды в отображении
   * По умолчанию: false
   */
  hideSeconds?: boolean
}

const props = defineProps<Props>()

const { __ } = vueLang()

const tooltipText = ref(__('frontend.common.copy'))
const showTooltip = ref(false)
let resetTimer: number | undefined
let tooltipElement = ref<HTMLElement | null>(null)
let triggerElement = ref<HTMLElement | null>(null)

function pad2(n: number): string {
  return n.toString().padStart(2, '0')
}

function formatWithPattern(date: Date, pattern: string, shortYear: boolean): string {
  const fullYear = date.getFullYear()
  const YYYY = String(fullYear)
  const YY = String(fullYear).slice(-2)
  const MM = pad2(date.getMonth() + 1)
  const DD = pad2(date.getDate())
  const HH = pad2(date.getHours())
  const mm = pad2(date.getMinutes())
  const ss = pad2(date.getSeconds())

  let result = pattern
  if (shortYear) {
    result = result.replace(/YYYY/g, YY)
  } else {
    result = result.replace(/YYYY/g, YYYY)
  }
  
  return result
    .replace(/MM/g, MM)
    .replace(/DD/g, DD)
    .replace(/HH/g, HH)
    .replace(/mm/g, mm)
    .replace(/ss/g, ss)
}

const dateObject = computed<Date | null>(() => {
  // Ожидаем ISO-строку
  const parsed = new Date(props.value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
})

const formatted = computed<string>(() => {
  if (!dateObject.value) return ''
  let pattern = props.pattern ?? 'YYYY.MM.DD HH:mm:ss'
  const shortYear = props.shortYear ?? true
  const hideSeconds = props.hideSeconds ?? false
  
  if (hideSeconds) {
    pattern = pattern.replace(/[: ]ss/g, '').replace(/ss/g, '')
  }
  
  return formatWithPattern(dateObject.value, pattern, shortYear)
})

const fullFormatted = computed<string>(() => {
  if (!dateObject.value) return ''
  const pattern = props.pattern ?? 'YYYY.MM.DD HH:mm:ss'
  return formatWithPattern(dateObject.value, pattern, false)
})

function updateTooltipPosition() {
  if (!tooltipElement.value || !triggerElement.value || !showTooltip.value) return

  const triggerRect = triggerElement.value.getBoundingClientRect()
  const tooltipRect = tooltipElement.value.getBoundingClientRect()

  let top = triggerRect.top - tooltipRect.height - 8
  let left = triggerRect.left + (triggerRect.width / 2) - (tooltipRect.width / 2)

  if (top < 0) {
    top = triggerRect.bottom + 8
  }

  if (left < 0) {
    left = 8
  } else if (left + tooltipRect.width > window.innerWidth) {
    left = window.innerWidth - tooltipRect.width - 8
  }

  tooltipElement.value.style.top = `${top}px`
  tooltipElement.value.style.left = `${left}px`
}

function handleScroll() {
  if (showTooltip.value) {
    updateTooltipPosition()
  }
}

function handleResize() {
  if (showTooltip.value) {
    updateTooltipPosition()
  }
}

function showTooltipHandler() {
  showTooltip.value = true
  requestAnimationFrame(() => {
    updateTooltipPosition()
  })
}

function hideTooltipHandler() {
  showTooltip.value = false
}

watch(showTooltip, (newValue) => {
  if (newValue) {
    window.addEventListener('scroll', handleScroll, true)
    window.addEventListener('resize', handleResize)
  } else {
    window.removeEventListener('scroll', handleScroll, true)
    window.removeEventListener('resize', handleResize)
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll, true)
  window.removeEventListener('resize', handleResize)
  if (resetTimer) clearTimeout(resetTimer)
})

async function copyToClipboard() {
  if (!fullFormatted.value) return
  try {
    await navigator.clipboard.writeText(fullFormatted.value)
    tooltipText.value = __('frontend.common.copied')
  } catch (_) {
    tooltipText.value = __('frontend.common.copy_failed')
  } finally {
    if (resetTimer) clearTimeout(resetTimer)
    resetTimer = window.setTimeout(() => {
      tooltipText.value = __('frontend.common.copy')
    }, 1500)
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    copyToClipboard()
  }
}
</script>

<template>
  <div class="relative inline-block">
    <span
      ref="triggerElement"
      class="cursor-pointer hover:text-primary transition-colors"
      :title="fullFormatted"
      @click="copyToClipboard"
      @keydown="onKeydown"
      @mouseenter="showTooltipHandler"
      @mouseleave="hideTooltipHandler"
      @focus="showTooltipHandler"
      @blur="hideTooltipHandler"
      tabindex="0"
      role="button"
    >
      {{ formatted }}
    </span>
    <Teleport to="body">
      <div
        v-if="showTooltip"
        ref="tooltipElement"
        class="fixed z-[9999] px-3 py-2 text-sm bg-base-300 text-base-content rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
        :style="{ top: '0px', left: '0px' }"
      >
        {{ tooltipText }}
      </div>
    </Teleport>
  </div>
</template>


