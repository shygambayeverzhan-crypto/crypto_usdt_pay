<script setup lang="ts">
import { computed } from 'vue';
import { imageUrl, isoToCountryCode } from 'flagpack-core';

interface Props {
    code: string;
    size?: 'S' | 'M' | 'L';
    hasBorder?: boolean;
    hasBorderRadius?: boolean;
    hasDropShadow?: boolean;
    gradient?: '' | 'top-down' | 'real-linear' | 'real-circular';
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'M',
    hasBorder: true,
    hasBorderRadius: true,
    hasDropShadow: false,
    gradient: '',
    className: '',
});

const url = computed(() => {
    const value = imageUrl(isoToCountryCode(props.code).toUpperCase(), props.size.toLowerCase());
    if (typeof value === 'string') return value;
    // Vite may wrap assets as modules with default export
    if (value && typeof value === 'object' && 'default' in value) {
        // @ts-expect-error runtime check
        return value.default as string;
    }
    return String(value ?? '');
});
</script>

<template>
    <div
        class="inline-flex overflow-hidden relative box-border"
        :class="[
            size === 'S' ? 'w-4 h-3' : size === 'L' ? 'w-8 h-6' : 'w-5 h-4',
            hasBorder ? 'fp-border' : '',
            hasBorderRadius ? 'fp-radius' : '',
            hasDropShadow ? 'fp-shadow' : '',
            gradient ? `fp-${gradient}` : '',
            className,
        ]"
    >
        <img :src="url" alt="" class="w-full h-full object-cover" />
    </div>
</template>

<style scoped>
.fp-border::before {
    content: '';
    position: absolute;
    inset: 0;
    border: 1px solid rgba(0, 0, 0, 0.5);
    mix-blend-mode: overlay;
    box-sizing: border-box;
}
.fp-radius::before {
    border-radius: 2px;
}
.fp-shadow {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
}
.fp-top-down::before {
    background-image: linear-gradient(
        0deg,
        rgba(0, 0, 0, 0.3) 2%,
        rgba(255, 255, 255, 0.7) 100%
    );
}
.fp-real-linear::before {
    background-image: linear-gradient(
        45deg,
        rgba(0, 0, 0, 0.2) 0,
        rgba(39, 39, 39, 0.22) 11%,
        rgba(255, 255, 255, 0.3) 27%,
        rgba(0, 0, 0, 0.24) 41%,
        rgba(0, 0, 0, 0.55) 52%,
        rgba(255, 255, 255, 0.26) 63%,
        rgba(0, 0, 0, 0.27) 74%,
        rgba(255, 255, 255, 0.3) 100%
    );
}
.fp-real-circular::before {
    background: radial-gradient(
                50% 36%,
                rgba(255, 255, 255, 0.3) 0,
                rgba(0, 0, 0, 0.24) 11%,
                rgba(0, 0, 0, 0.55) 17%,
                rgba(255, 255, 255, 0.26) 22%,
                rgba(0, 0, 0, 0.17) 27%,
                rgba(255, 255, 255, 0.28) 31%,
                rgba(255, 255, 255, 0) 37%
            )
            center calc(50% - 8px) / 600% 600%,
        radial-gradient(
                50% 123%,
                rgba(255, 255, 255, 0.3) 25%,
                rgba(0, 0, 0, 0.24) 48%,
                rgba(0, 0, 0, 0.55) 61%,
                rgba(255, 255, 255, 0.26) 72%,
                rgba(0, 0, 0, 0.17) 80%,
                rgba(255, 255, 255, 0.28) 88%,
                rgba(255, 255, 255, 0.3) 100%
            )
            center calc(50% - 8px) / 600% 600%;
}
</style>

