<script setup lang="ts">
interface Option {
    value: string | number;
    label: string;
    disabled?: boolean;
}

interface Props {
    id?: string;
    modelValue: string | number;
    options: Option[];
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    name?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

function handleChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <select
        :id="props.id"
        :name="props.name"
        :value="props.modelValue"
        :required="props.required"
        :disabled="props.disabled"
        class="select select-bordered w-full"
        @change="handleChange"
    >
        <option v-if="props.placeholder" value="" disabled>{{ props.placeholder }}</option>
        <option v-for="opt in props.options" :key="opt.value" :value="opt.value" :disabled="opt.disabled">
            {{ opt.label }}
        </option>
    </select>
</template>

