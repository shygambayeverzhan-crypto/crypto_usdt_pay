<script setup lang="ts">
import { useAttrs } from 'vue';
import { Link } from '@inertiajs/vue3';

interface Props {
  href: string;
  title?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
});

const attrs = useAttrs();
</script>

<template>
  <span v-if="props.disabled" v-bind="attrs" class="btn btn-xs btn-secondary btn-disabled" :title="props.title" :aria-label="props.title" aria-disabled="true">
    <slot />
  </span>
  <Link v-else v-bind="attrs" :href="props.href" class="btn btn-xs btn-secondary" :title="props.title" :aria-label="props.title">
    <slot />
  </Link>
</template>


