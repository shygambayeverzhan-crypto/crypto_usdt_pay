<script setup lang="ts">
import { computed } from 'vue';

type InputSize = 'xs' | 'sm' | 'md' | 'lg';

interface Props {
    id?: string;
    modelValue: string | number;
    type?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    name?: string;
    step?: string | number;
    min?: string | number;
    max?: string | number;
    size?: InputSize;
    readonly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    size: 'md',
    readonly: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const inputClasses = computed(() => `input input-bordered w-full input-${props.size}`);

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', props.type === 'number' ? Number(target.value) : target.value);
}
</script>

<template>
    <input
        :id="props.id"
        :type="props.type"
        :name="props.name"
        :value="props.modelValue"
        :placeholder="props.placeholder"
        :required="props.required"
        :readonly="props.readonly"
        :disabled="props.disabled"
        :autofocus="props.autofocus"
        :autocomplete="props.autocomplete"
        :step="props.step"
        :min="props.min"
        :max="props.max"
        :class="inputClasses"
        @input="handleInput"
    />
</template>

