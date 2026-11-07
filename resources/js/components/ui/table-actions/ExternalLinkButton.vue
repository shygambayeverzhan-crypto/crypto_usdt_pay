<script setup lang="ts">
import { useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

interface Props {
  href: string;
  title?: string;
  target?: string;
  rel?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  target: '_blank',
  rel: 'noopener noreferrer',
  disabled: false,
});

const attrs = useAttrs();
</script>

<template>
  <a
    v-bind="attrs"
    :href="props.disabled ? undefined : props.href"
    class="btn btn-xs btn-accent"
    :title="props.title"
    :aria-label="props.title"
    :target="props.target"
    :rel="props.rel"
    :aria-disabled="props.disabled ? 'true' : 'false'"
    :tabindex="props.disabled ? -1 : undefined"
  >
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
    </svg>
  </a>
</template>


