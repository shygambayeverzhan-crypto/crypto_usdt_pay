<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

interface ClientOption {
    value: string
    label: string
    contact?: string | null
    external_id?: string
}

interface Props {
    modelValue: string | null
    options: ClientOption[]
    placeholder?: string
    emptyLabel?: string
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    options: () => [],
    placeholder: '',
    emptyLabel: '',
    disabled: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void
}>()

const search = ref('')
const open = ref(false)

const filtered = computed(() => {
    const term = search.value.toLowerCase()
    return props.options.filter((option) => {
        if (!term) return true
        return (
            option.label.toLowerCase().includes(term) ||
            (option.value?.toLowerCase().includes(term)) ||
            (option.contact?.toLowerCase().includes(term) ?? false)
        )
    })
})

watch(
    () => props.modelValue,
    (val) => {
        const current = props.options.find((o) => String(o.value) === String(val ?? ''))
        search.value = current?.label || current?.value || val || ''
    },
    { immediate: true }
)

watch(
    () => props.options,
    () => {
        const current = props.options.find((o) => String(o.value) === String(props.modelValue ?? ''))
        if (current) {
            search.value = current.label || current.value
        }
    },
)

function select(option: ClientOption) {
    emit('update:modelValue', option.value)
    search.value = option.label || option.value
    open.value = false
}

function onInput(value: string) {
    search.value = value
    emit('update:modelValue', value || null)
    open.value = true
}

function handleBlur(event: FocusEvent) {
    const current = event.currentTarget as HTMLElement | null
    const related = event.relatedTarget as HTMLElement | null
    if (!current) return
    if (!related || !current.contains(related)) {
        open.value = false
    }
}

onMounted(() => {
    if (props.modelValue) {
        const current = props.options.find((o) => String(o.value) === String(props.modelValue))
        search.value = current?.label || current?.value || ''
    }
})
</script>

<template>
    <div class="relative" @focusout="handleBlur">
        <input
            type="text"
            class="input input-bordered w-full"
            :class="{ 'opacity-60': disabled }"
            :placeholder="placeholder"
            :disabled="disabled"
            :value="search"
            @input="onInput(($event.target as HTMLInputElement).value)"
            @focus="open = true"
        />
        <div v-if="open" class="absolute z-10 mt-1 w-full bg-base-100 border border-base-200 rounded-box shadow max-h-64 overflow-auto">
            <button
                v-for="option in filtered"
                :key="option.value"
                type="button"
                class="w-full px-3 py-2 text-left hover:bg-base-200 flex items-center gap-2"
                @mousedown.prevent="select(option)"
            >
                <div class="flex-1 min-w-0">
                    <div class="font-medium truncate">{{ option.label || option.value }}</div>
                    <div v-if="option.contact" class="text-xs opacity-70 truncate">ID: {{ option.external_id }}</div>
                </div>
                <span v-if="String(modelValue ?? '') === String(option.value)" class="text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                    </svg>
                </span>
            </button>
            <div v-if="!filtered.length" class="px-3 py-2 text-sm opacity-70">
                {{ emptyLabel || placeholder }}
            </div>
        </div>
    </div>
</template>


