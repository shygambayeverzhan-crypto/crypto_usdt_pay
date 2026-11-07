<script setup lang="ts">
interface Props {
    error?: string;
    hint?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="form-control flex flex-col">
        <slot />
        <label v-if="props.error || props.hint" class="label">
            <span v-if="props.error" class="label-text-alt text-error">{{ props.error }}</span>
            <span v-else-if="props.hint" class="label-text-alt opacity-60">{{ props.hint }}</span>
        </label>
    </div>
</template>

