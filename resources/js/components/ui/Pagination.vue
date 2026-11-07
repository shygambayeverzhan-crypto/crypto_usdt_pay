<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  links?: (PaginationLink | null)[];
}

const props = defineProps<Props>();
const safeLinks = computed<PaginationLink[]>(() => {
  return (props.links ?? []).filter((link): link is PaginationLink => Boolean(link));
});
</script>

<template>
  <div class="mt-4 flex gap-2 flex-wrap">
    <Link
      v-for="link in safeLinks"
      :key="link.url || link.label"
      :href="link.url || '#'"
      class="btn btn-sm"
      :class="{
        'btn-disabled': !link.url,
        'btn-active': link.active,
      }"
      v-html="link.label"
    />
  </div>
</template>





