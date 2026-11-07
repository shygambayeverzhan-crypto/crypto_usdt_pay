<script setup lang="ts">
import { computed, ref, watch } from 'vue';
interface Props {
    currency?: string | null;
    currencyLabel: string;
    network?: string | null;
    networkLabel: string;
    size?: 'md' | 'lg';
    iconSize?: number | string | null;
}

const props = withDefaults(defineProps<Props>(), {
    currency: null,
    network: null,
    iconSize: null,
});

const size = computed(() => props.size ?? 'md');

const currencyClass = computed(() => {
    return size.value === 'lg' ? 'text-lg' : '';
});

const networkBadgeClass = computed(() => {
    // current default look uses badge-xs; for lg make it one step larger
    return size.value === 'lg' ? 'badge badge-sm badge-neutral' : 'badge badge-xs badge-neutral';
});

const formattedNetworkLabel = computed(() => {
    const source = (props.networkLabel ?? '').trim();
    if (source.length === 0) return '';
    const lower = source.toLowerCase();
    return lower.charAt(0).toUpperCase() + lower.slice(1);
});

const network_icon_map: Record<string, string> = {
    tron: 'trx',
};

function toCssSize(value: number | string | null | undefined): string | undefined {
    if (value === undefined || value === null) return undefined;
    if (typeof value === 'number') return `${value}px`;
    return value;
}

function normalizeIconKey(value: string): string {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');
}

const icon_currency_key = computed(() => {
    const value = String(props.currency ?? '').trim();
    return normalizeIconKey(value || props.currencyLabel);
});

const icon_network_key = computed(() => {
    const value = String(props.network ?? '').trim().toLowerCase();
    const raw = value || String(props.networkLabel ?? '').trim().toLowerCase();
    if (!raw) return '';
    return network_icon_map[raw] ?? normalizeIconKey(raw);
});

const currency_src = computed(() => {
    if (!icon_currency_key.value) return '';
    return `/icon/${icon_currency_key.value}.svg`;
});

const network_src = computed(() => {
    if (!icon_network_key.value) return '';
    return `/icon/${icon_network_key.value}.svg`;
});

const icon_style = computed(() => {
    const size = toCssSize(props.iconSize);
    return {
        width: size,
        height: size,
    };
});

const currency_failed = ref(false);
const network_failed = ref(false);

watch(
    () => [currency_src.value, network_src.value],
    () => {
        currency_failed.value = false;
        network_failed.value = false;
    }
);

const fallback_text = computed(() => {
    const value = String(props.currency ?? props.currencyLabel ?? '').trim().toUpperCase();
    if (!value) return '?';
    return value.slice(0, 3);
});
</script>

<template>
    <span class="space-x-2 flex items-center">
        <span v-if="props.iconSize" class="relative inline-flex shrink-0" :style="icon_style">
            <img
                v-if="currency_src && !currency_failed"
                :src="currency_src"
                alt=""
                class="w-full h-full object-contain"
                @error="currency_failed = true"
            />
            <span
                v-else
                class="w-full h-full rounded-full bg-base-200 text-base-content/70 inline-flex items-center justify-center text-[10px] font-semibold"
            >
                {{ fallback_text }}
            </span>

            <span
                v-if="network_src && !network_failed"
                class="absolute -bottom-0.5 -right-0.5 w-[62%] h-[62%] rounded-full bg-base-100"
            >
                <img :src="network_src" alt="" class="w-full h-full object-contain" @error="network_failed = true" />
            </span>
        </span>

        <span class="space-x-1 inline-flex items-center">
            <span class="text-sm text-base-content" :class="currencyClass">{{ props.currencyLabel }}</span>
            <span v-if="formattedNetworkLabel" :class="networkBadgeClass">{{ formattedNetworkLabel }}</span>
        </span>
    </span>
</template>


