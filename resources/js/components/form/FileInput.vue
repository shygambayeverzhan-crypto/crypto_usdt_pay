<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

interface Props {
    id?: string;
    name?: string;
    modelValue: File | null;
    accept?: string;
    required?: boolean;
    disabled?: boolean;
    clearable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    clearable: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
}>();

const inputRef = ref<HTMLInputElement | null>(null);

function handleChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    emit('update:modelValue', file);
}

function clear() {
    if (inputRef.value) {
        inputRef.value.value = '';
    }
    emit('update:modelValue', null);
}

watch(
    () => props.modelValue,
    (value) => {
        if (!value && inputRef.value) {
            inputRef.value.value = '';
        }
    }
);

onMounted(() => {
    if (!props.modelValue && inputRef.value) {
        inputRef.value.value = '';
    }
});
</script>

<template>
    <div class="flex items-center gap-2">
        <input
            :id="props.id"
            :name="props.name"
            ref="inputRef"
            type="file"
            :accept="props.accept"
            :required="props.required"
            :disabled="props.disabled"
            class="file-input file-input-bordered w-full"
            @change="handleChange"
        />
        <button
            v-if="props.clearable && props.modelValue"
            type="button"
            class="btn btn-ghost btn-square"
            :disabled="props.disabled"
            @click="clear"
        >
            ✕
        </button>
    </div>
</template>

