<script setup lang="ts">
interface Props {
    for?: string;
    required?: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <label :for="props.for" class="label">
        <span class="label-text">
            <slot />
            <span v-if="required" class="text-error">*</span>
        </span>
    </label>
</template>





