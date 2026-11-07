<script setup lang="ts">
interface Props {
    id?: string;
    modelValue: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    rows?: number;
    name?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rows: 4,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function handleInput(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <textarea
        :id="props.id"
        :name="props.name"
        :value="props.modelValue"
        :placeholder="props.placeholder"
        :required="props.required"
        :disabled="props.disabled"
        :rows="props.rows"
        class="textarea textarea-bordered w-full"
        @input="handleInput"
    />
</template>

