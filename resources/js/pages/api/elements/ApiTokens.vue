<script setup lang="ts">
import { computed, ref, onUnmounted, watch } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';
import { router } from '@inertiajs/vue3';
import ConfirmDialog from '@/components/ui/modal/ConfirmDialog.vue';

interface Props {
    apiKey: string;
    callbackToken: string;
    apiBase: string;
}

const props = defineProps<Props>();
const { __ } = vueLang();

const truncatedKey = computed(() => {
    const src = (props.apiKey ?? '').trim();
    if (src.length <= 20) return src;
    const start = src.slice(0, 10);
    const end = src.slice(-10);
    return `${start}...${end}`;
});

const truncatedBase = computed(() => {
    const src = (props.apiBase ?? '').trim();
    if (src.length <= 30) return src;
    const start = src.slice(0, 15);
    const end = src.slice(-15);
    return `${start}...${end}`;
});

const truncatedCallbackToken = computed(() => {
    const src = (props.callbackToken ?? '').trim();
    if (src.length <= 20) return src;
    const start = src.slice(0, 10);
    const end = src.slice(-10);
    return `${start}...${end}`;
});

// Tooltip state for API Key
const tooltipTextKey = ref(__('frontend.api.token.copy'));
const showTooltipKey = ref(false);
let resetTimerKey: number | undefined;
const tooltipElementKey = ref<HTMLElement | null>(null);
const triggerElementKey = ref<HTMLElement | null>(null);

// Tooltip state for API Base
const tooltipTextBase = ref(__('frontend.api.token.copy'));
const showTooltipBase = ref(false);
let resetTimerBase: number | undefined;
const tooltipElementBase = ref<HTMLElement | null>(null);
const triggerElementBase = ref<HTMLElement | null>(null);

const tooltipTextCallback = ref(__('frontend.api.token.copy'));
const showTooltipCallback = ref(false);
let resetTimerCallback: number | undefined;
const tooltipElementCallback = ref<HTMLElement | null>(null);
const triggerElementCallback = ref<HTMLElement | null>(null);

// Hover state for blur effect
const isHoveredKey = ref(false);
const isHoveredBase = ref(false);
const isHoveredCallback = ref(false);

// Regenerate token modal
const showRegenerateModal = ref(false);
const showRegenerateCallbackModal = ref(false);
const isRegenerating = ref(false);
const isRegeneratingCallback = ref(false);

function updateTooltipPosition(element: HTMLElement | null, trigger: HTMLElement | null) {
    if (!element || !trigger || (!showTooltipKey.value && !showTooltipBase.value && !showTooltipCallback.value)) return;

    const triggerRect = trigger.getBoundingClientRect();
    const tooltipRect = element.getBoundingClientRect();

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

    element.style.top = `${top}px`;
    element.style.left = `${left}px`;
}

function handleScrollKey() {
    if (showTooltipKey.value) {
        updateTooltipPosition(tooltipElementKey.value, triggerElementKey.value);
    }
}

function handleResizeKey() {
    if (showTooltipKey.value) {
        updateTooltipPosition(tooltipElementKey.value, triggerElementKey.value);
    }
}

function handleScrollBase() {
    if (showTooltipBase.value) {
        updateTooltipPosition(tooltipElementBase.value, triggerElementBase.value);
    }
}

function handleResizeBase() {
    if (showTooltipBase.value) {
        updateTooltipPosition(tooltipElementBase.value, triggerElementBase.value);
    }
}

function handleScrollCallback() {
    if (showTooltipCallback.value) {
        updateTooltipPosition(tooltipElementCallback.value, triggerElementCallback.value);
    }
}

function handleResizeCallback() {
    if (showTooltipCallback.value) {
        updateTooltipPosition(tooltipElementCallback.value, triggerElementCallback.value);
    }
}

function showTooltipHandler(type: 'key' | 'base' | 'callback') {
    if (type === 'key') {
        showTooltipKey.value = true;
        requestAnimationFrame(() => {
            updateTooltipPosition(tooltipElementKey.value, triggerElementKey.value);
        });
    } else if (type === 'callback') {
        showTooltipCallback.value = true;
        requestAnimationFrame(() => {
            updateTooltipPosition(tooltipElementCallback.value, triggerElementCallback.value);
        });
    } else {
        showTooltipBase.value = true;
        requestAnimationFrame(() => {
            updateTooltipPosition(tooltipElementBase.value, triggerElementBase.value);
        });
    }
}

function hideTooltipHandler(type: 'key' | 'base' | 'callback') {
    if (type === 'key') {
        showTooltipKey.value = false;
    } else if (type === 'callback') {
        showTooltipCallback.value = false;
    } else {
        showTooltipBase.value = false;
    }
}

function onMouseEnter(type: 'key' | 'base' | 'callback') {
    if (type === 'key') {
        isHoveredKey.value = true;
    } else if (type === 'callback') {
        isHoveredCallback.value = true;
    } else {
        isHoveredBase.value = true;
    }
    showTooltipHandler(type);
}

function onMouseLeave(type: 'key' | 'base' | 'callback') {
    if (type === 'key') {
        isHoveredKey.value = false;
    } else if (type === 'callback') {
        isHoveredCallback.value = false;
    } else {
        isHoveredBase.value = false;
    }
    hideTooltipHandler(type);
}

watch(showTooltipKey, (newValue) => {
    if (newValue) {
        window.addEventListener('scroll', handleScrollKey, true);
        window.addEventListener('resize', handleResizeKey);
    } else {
        window.removeEventListener('scroll', handleScrollKey, true);
        window.removeEventListener('resize', handleResizeKey);
    }
});

watch(showTooltipBase, (newValue) => {
    if (newValue) {
        window.addEventListener('scroll', handleScrollBase, true);
        window.addEventListener('resize', handleResizeBase);
    } else {
        window.removeEventListener('scroll', handleScrollBase, true);
        window.removeEventListener('resize', handleResizeBase);
    }
});

watch(showTooltipCallback, (newValue) => {
    if (newValue) {
        window.addEventListener('scroll', handleScrollCallback, true);
        window.addEventListener('resize', handleResizeCallback);
    } else {
        window.removeEventListener('scroll', handleScrollCallback, true);
        window.removeEventListener('resize', handleResizeCallback);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScrollKey, true);
    window.removeEventListener('resize', handleResizeKey);
    window.removeEventListener('scroll', handleScrollBase, true);
    window.removeEventListener('resize', handleResizeBase);
    window.removeEventListener('scroll', handleScrollCallback, true);
    window.removeEventListener('resize', handleResizeCallback);
    if (resetTimerKey) clearTimeout(resetTimerKey);
    if (resetTimerBase) clearTimeout(resetTimerBase);
    if (resetTimerCallback) clearTimeout(resetTimerCallback);
});

async function copyToClipboard(text: string, type: 'key' | 'base' | 'callback') {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        if (type === 'key') {
            tooltipTextKey.value = __('frontend.api.token.copied');
        } else if (type === 'callback') {
            tooltipTextCallback.value = __('frontend.api.token.copied');
        } else {
            tooltipTextBase.value = __('frontend.api.token.copied');
        }
    } catch (_) {
        if (type === 'key') {
            tooltipTextKey.value = __('frontend.api.token.copy_failed');
        } else if (type === 'callback') {
            tooltipTextCallback.value = __('frontend.api.token.copy_failed');
        } else {
            tooltipTextBase.value = __('frontend.api.token.copy_failed');
        }
    } finally {
        const timer = type === 'key' ? resetTimerKey : (type === 'callback' ? resetTimerCallback : resetTimerBase);
        if (timer) clearTimeout(timer);
        const newTimer = window.setTimeout(() => {
            if (type === 'key') {
                tooltipTextKey.value = __('frontend.api.token.copy');
            } else if (type === 'callback') {
                tooltipTextCallback.value = __('frontend.api.token.copy');
            } else {
                tooltipTextBase.value = __('frontend.api.token.copy');
            }
        }, 1500);
        if (type === 'key') {
            resetTimerKey = newTimer;
        } else if (type === 'callback') {
            resetTimerCallback = newTimer;
        } else {
            resetTimerBase = newTimer;
        }
    }
}

function onKeydown(e: KeyboardEvent, type: 'key' | 'base' | 'callback') {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const text = type === 'key' ? props.apiKey : (type === 'callback' ? props.callbackToken : props.apiBase);
        copyToClipboard(text, type);
    }
}

function openRegenerateModal() {
    showRegenerateModal.value = true;
}

function closeRegenerateModal() {
    showRegenerateModal.value = false;
}

function openRegenerateCallbackModal() {
    showRegenerateCallbackModal.value = true;
}

function closeRegenerateCallbackModal() {
    showRegenerateCallbackModal.value = false;
}

async function regenerateToken() {
    isRegenerating.value = true;
    router.post('/api/regenerate-token', {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeRegenerateModal();
        },
        onFinish: () => {
            isRegenerating.value = false;
        },
    });
}

async function regenerateCallbackToken() {
    isRegeneratingCallback.value = true;
    router.post('/api/regenerate-callback-token', {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeRegenerateCallbackModal();
        },
        onFinish: () => {
            isRegeneratingCallback.value = false;
        },
    });
}
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-0 p-4 sm:p-5">
            <h3 class="card-title text-base mb-2">{{ __('frontend.api.token.title') }}</h3>
            <div class="form-control">
                <label class="label py-1">
                    <span class="label-text text-xs">{{ __('frontend.api.token.x_api_key') }}</span>
                </label>
                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-2 sm:items-center">
                    <div class="relative min-w-0 h-11">
                        <span
                            ref="triggerElementKey"
                            class="font-mono text-xs sm:text-sm cursor-pointer hover:text-primary transition-colors box-border flex h-11 w-full min-w-0 items-center px-2 sm:px-2.5 bg-base-200 rounded-md border border-base-300 overflow-hidden"
                            :title="apiKey"
                            @click="copyToClipboard(apiKey, 'key')"
                            @keydown="(e) => onKeydown(e, 'key')"
                            @mouseenter="onMouseEnter('key')"
                            @mouseleave="onMouseLeave('key')"
                            @focus="showTooltipHandler('key')"
                            @blur="hideTooltipHandler('key')"
                            tabindex="0"
                            role="button"
                        >
                            <span :class="['hidden sm:inline break-all']" :style="{ filter: !isHoveredKey ? 'blur(3px)' : 'none' }">{{ apiKey }}</span>
                            <span :class="['inline sm:hidden']" :style="{ filter: !isHoveredKey ? 'blur(3px)' : 'none' }">{{ truncatedKey }}</span>
                        </span>
                        <Teleport to="body">
                            <div
                                v-if="showTooltipKey"
                                ref="tooltipElementKey"
                                class="fixed z-[9999] px-3 py-2 text-sm bg-base-300 text-base-content rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                                :style="{ top: '0px', left: '0px' }"
                            >
                                {{ tooltipTextKey }}
                            </div>
                        </Teleport>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-11 w-full shrink-0 cursor-pointer items-center justify-center gap-1.5 box-border rounded-md border border-error/70 bg-base-100 px-3 text-xs font-medium text-error transition-colors hover:border-error hover:bg-error/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error/50 disabled:pointer-events-none disabled:opacity-40 sm:w-auto"
                        :title="__('frontend.api.token.regenerate')"
                        :disabled="isRegenerating"
                        @click="openRegenerateModal"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('frontend.api.token.regenerate_short') }}
                    </button>
                </div>
            </div>
            <div class="form-control mt-3">
                <label class="label py-1">
                    <span class="label-text text-xs">{{ __('frontend.api.token.callback_token') }}</span>
                </label>
                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-2 sm:items-center">
                    <div class="relative min-w-0 h-11">
                        <span
                            ref="triggerElementCallback"
                            class="font-mono text-xs sm:text-sm cursor-pointer hover:text-primary transition-colors box-border flex h-11 w-full min-w-0 items-center px-2 sm:px-2.5 bg-base-200 rounded-md border border-base-300 overflow-hidden"
                            :title="callbackToken"
                            @click="copyToClipboard(callbackToken, 'callback')"
                            @keydown="(e) => onKeydown(e, 'callback')"
                            @mouseenter="onMouseEnter('callback')"
                            @mouseleave="onMouseLeave('callback')"
                            @focus="showTooltipHandler('callback')"
                            @blur="hideTooltipHandler('callback')"
                            tabindex="0"
                            role="button"
                        >
                            <span :class="['hidden sm:inline break-all']" :style="{ filter: !isHoveredCallback ? 'blur(3px)' : 'none' }">{{ callbackToken }}</span>
                            <span :class="['inline sm:hidden']" :style="{ filter: !isHoveredCallback ? 'blur(3px)' : 'none' }">{{ truncatedCallbackToken }}</span>
                        </span>
                        <Teleport to="body">
                            <div
                                v-if="showTooltipCallback"
                                ref="tooltipElementCallback"
                                class="fixed z-[9999] px-3 py-2 text-sm bg-base-300 text-base-content rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                                :style="{ top: '0px', left: '0px' }"
                            >
                                {{ tooltipTextCallback }}
                            </div>
                        </Teleport>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-11 w-full shrink-0 cursor-pointer items-center justify-center gap-1.5 box-border rounded-md border border-error/70 bg-base-100 px-3 text-xs font-medium text-error transition-colors hover:border-error hover:bg-error/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error/50 disabled:pointer-events-none disabled:opacity-40 sm:w-auto"
                        :title="__('frontend.api.token.regenerate_callback')"
                        :disabled="isRegeneratingCallback"
                        @click="openRegenerateCallbackModal"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('frontend.api.token.regenerate_short') }}
                    </button>
                </div>
            </div>
            <div class="form-control mt-3">
                <label class="label py-1">
                    <span class="label-text text-xs">{{ __('frontend.api.token.api_base') }}</span>
                </label>
                <div class="relative w-full">
                    <span
                        ref="triggerElementBase"
                        class="font-mono text-xs sm:text-sm cursor-pointer hover:text-primary transition-colors block py-1.5 px-2 sm:py-2 sm:px-2.5 bg-base-200 rounded-md border border-base-300 overflow-hidden"
                        :title="apiBase"
                        @click="copyToClipboard(apiBase, 'base')"
                        @keydown="(e) => onKeydown(e, 'base')"
                        @mouseenter="onMouseEnter('base')"
                        @mouseleave="onMouseLeave('base')"
                        @focus="showTooltipHandler('base')"
                        @blur="hideTooltipHandler('base')"
                        tabindex="0"
                        role="button"
                    >
                        <span class="hidden sm:inline break-all">{{ apiBase }}</span>
                        <span class="inline sm:hidden">{{ truncatedBase }}</span>
                    </span>
                    <Teleport to="body">
                        <div
                            v-if="showTooltipBase"
                            ref="tooltipElementBase"
                            class="fixed z-[9999] px-3 py-2 text-sm bg-base-300 text-base-content rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                            :style="{ top: '0px', left: '0px' }"
                        >
                            {{ tooltipTextBase }}
                        </div>
                    </Teleport>
                </div>
            </div>
        </div>
    </div>

    <ConfirmDialog
        v-model="showRegenerateModal"
        :title="__('frontend.api.token.regenerate_confirm_title')"
        :message="__('frontend.api.token.regenerate_confirm_message')"
        :confirm-text="__('frontend.api.token.regenerate_short')"
        :loading="isRegenerating"
        danger
        @confirm="regenerateToken"
        @cancel="closeRegenerateModal"
    />

    <ConfirmDialog
        v-model="showRegenerateCallbackModal"
        :title="__('frontend.api.token.regenerate_callback_confirm_title')"
        :message="__('frontend.api.token.regenerate_callback_confirm_message')"
        :confirm-text="__('frontend.api.token.regenerate_short')"
        :loading="isRegeneratingCallback"
        danger
        @confirm="regenerateCallbackToken"
        @cancel="closeRegenerateCallbackModal"
    />
</template>

