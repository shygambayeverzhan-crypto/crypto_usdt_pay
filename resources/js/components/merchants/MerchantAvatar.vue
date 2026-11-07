<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    name?: string;
    initials?: string | null;
    logoUrl?: string | null;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    name: '',
    initials: '',
    logoUrl: null,
});

const sizeClass = computed(() => {
    const map = {
        sm: { circle: 'w-8 h-8', text: 'text-sm' },
        md: { circle: 'w-10 h-10', text: 'text-base' },
        lg: { circle: 'w-12 h-12', text: 'text-lg' },
    };
    return map[props.size] ?? map.md;
});

const fallback = computed(() => {
    const initials = (props.initials || props.name || '').trim();
    if (!initials) return '?';
    return initials.slice(0, 2).toUpperCase();
});
</script>

<template>
    <div class="avatar placeholder">
        <div
            class="bg-primary text-primary-content rounded-full overflow-hidden flex items-center justify-center"
            :class="sizeClass.circle"
        >
            <img
                v-if="props.logoUrl"
                :src="props.logoUrl"
                :alt="`${props.name || 'Merchant'} logo`"
                class="w-full h-full object-cover"
            />
            <span v-else class="font-semibold tracking-wide uppercase" :class="sizeClass.text">
                {{ fallback }}
            </span>
        </div>
    </div>
</template>


