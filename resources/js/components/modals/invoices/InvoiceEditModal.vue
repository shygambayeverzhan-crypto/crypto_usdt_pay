<script setup lang="ts">
import { computed } from 'vue'
import ModalDialog from '@/components/ui/modal/ModalDialog.vue'
import FormControl from '@/components/form/FormControl.vue'
import Label from '@/components/form/Label.vue'
import Input from '@/components/form/Input.vue'
import Select from '@/components/form/Select.vue'
import { vueLang } from '@erag/lang-sync-inertia'

interface Option { value: string; label: string }

export interface InvoiceEditForm {
    status: string
    txid: string | null
}

type InvoiceEditErrors = Partial<Record<keyof InvoiceEditForm, string>>

interface Props {
    modelValue: boolean
    form: InvoiceEditForm
    statusOptions: Option[]
    errors: InvoiceEditErrors | null
    loading: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    loading: false,
    form: () => ({ status: '', txid: null }),
    statusOptions: () => [],
    errors: () => ({} as InvoiceEditErrors),
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:form', value: InvoiceEditForm): void
    (e: 'submit'): void
    (e: 'close'): void
}>()

const form = computed({
    get: () => props.form,
    set: (value: InvoiceEditForm) => emit('update:form', value),
})

const fieldErrors = computed<InvoiceEditErrors>(() => props.errors ?? {})

const { __ } = vueLang()

const txidInput = computed({
    get: () => form.value.txid || '',
    set: (value: string) => {
        emit('update:form', {
            ...form.value,
            txid: value.trim() || null,
        })
    },
})

function close() {
    emit('update:modelValue', false)
    emit('close')
}

function submit() {
    const trimmedForm = {
        ...form.value,
        txid: form.value.txid ? form.value.txid.trim() : null,
    }
    emit('update:form', trimmedForm)
    emit('submit')
}
</script>

<template>
    <ModalDialog
        :model-value="modelValue"
        :title="__('frontend.invoices.modals.edit.title')"
        :description="__('frontend.invoices.modals.edit.description')"
        size="xl"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <form class="mt-4 grid gap-4" @submit.prevent="submit">
            <FormControl :error="fieldErrors.status">
                <Label for="status" required>{{ __('frontend.invoices.fields.status') }}</Label>
                <Select
                    id="status"
                    v-model="form.status"
                    :options="statusOptions"
                    required
                />
            </FormControl>
            <FormControl v-if="form.status === 'paid'" :hint="__('frontend.invoices.fields.txid_hint')" :error="fieldErrors.txid">
                <Label for="txid" required>{{ __('frontend.invoices.fields.txid') }}</Label>
                <Input
                    id="txid"
                    v-model="txidInput"
                    type="text"
                    :placeholder="__('frontend.invoices.fields.txid_placeholder')"
                    required
                />
            </FormControl>
        </form>

        <template #actions>
            <button type="button" class="btn" @click="close" :disabled="loading">{{ __('frontend.common.cancel') }}</button>
            <button type="button" class="btn btn-primary" :class="{ loading }" :disabled="loading" @click="submit">
                {{ __('frontend.invoices.actions.save') }}
            </button>
        </template>
    </ModalDialog>
</template>


