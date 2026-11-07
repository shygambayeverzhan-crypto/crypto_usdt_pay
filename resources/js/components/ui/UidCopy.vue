<script setup lang="ts">
import { computed, ref, onUnmounted, watch } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
    uid: string;
}

const props = defineProps<Props>();

const { __ } = vueLang();

const tooltipText = ref(__('frontend.common.copy'));
const showTooltip = ref(false);
let resetTimer: number | undefined;
let tooltipElement = ref<HTMLElement | null>(null);
let triggerElement = ref<HTMLElement | null>(null);

const shortened = computed(() => {
    const src = (props.uid ?? '').trim();
    if (src.length <= 8) return src;
    const end = src.slice(-8);
    return `${end}`;
});

function updateTooltipPosition() {
    if (!tooltipElement.value || !triggerElement.value || !showTooltip.value) return;

    const triggerRect = triggerElement.value.getBoundingClientRect();
    const tooltipRect = tooltipElement.value.getBoundingClientRect();

    let top = triggerRect.top - tooltipRect.height - 8;
    let left = triggerRect.left + (triggerRect.width / 2) - (tooltipRect.width / 2);

    if (top < 0) {
        top = triggerRect.bottom + 8;
    }

    if (left < 0) {
        left = 8;
    } else if (left + tooltipRect.width > window.innerWidth) {
        left = window.innerWidth - tooltipRect.width - 8;
    }

    tooltipElement.value.style.top = `${top}px`;
    tooltipElement.value.style.left = `${left}px`;
}

function handleScroll() {
    if (showTooltip.value) {
        updateTooltipPosition();
    }
}

function handleResize() {
    if (showTooltip.value) {
        updateTooltipPosition();
    }
}

function showTooltipHandler() {
    showTooltip.value = true;
    requestAnimationFrame(() => {
        updateTooltipPosition();
    });
}

function hideTooltipHandler() {
    showTooltip.value = false;
}

watch(showTooltip, (newValue) => {
    if (newValue) {
        window.addEventListener('scroll', handleScroll, true);
        window.addEventListener('resize', handleResize);
    } else {
        window.removeEventListener('scroll', handleScroll, true);
        window.removeEventListener('resize', handleResize);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll, true);
    window.removeEventListener('resize', handleResize);
    if (resetTimer) clearTimeout(resetTimer);
});

async function copyToClipboard() {
    try {
        await navigator.clipboard.writeText(props.uid);
        tooltipText.value = __('frontend.common.copied');
    } catch (_) {
        tooltipText.value = __('frontend.common.copy_failed');
    } finally {
        if (resetTimer) clearTimeout(resetTimer);
        resetTimer = window.setTimeout(() => {
            tooltipText.value = __('frontend.common.copy');
        }, 1500);
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        copyToClipboard();
    }
}
</script>

<template>
    <div class="relative inline-block">
        <span
            ref="triggerElement"
            class="font-mono cursor-pointer hover:text-primary transition-colors"
            :title="props.uid"
            @click="copyToClipboard"
            @keydown="onKeydown"
            @mouseenter="showTooltipHandler"
            @mouseleave="hideTooltipHandler"
            @focus="showTooltipHandler"
            @blur="hideTooltipHandler"
            tabindex="0"
            role="button"
        >
            {{ shortened }}
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


