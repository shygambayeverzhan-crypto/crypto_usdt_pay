<script setup lang="ts">
import { computed } from 'vue'
import ModalDialog from '@/components/ui/modal/ModalDialog.vue'
import FormControl from '@/components/form/FormControl.vue'
import Label from '@/components/form/Label.vue'
import Input from '@/components/form/Input.vue'
import Textarea from '@/components/form/Textarea.vue'
import { vueLang } from '@erag/lang-sync-inertia'

export interface ClientForm {
    external_id: string
    name: string
    telegram: string
    contact: string
}

type ClientFormErrors = Partial<Record<keyof ClientForm, string>>

interface Props {
    modelValue: boolean
    form: ClientForm
    errors: ClientFormErrors | null
    loading: boolean
    mode?: 'create' | 'edit'
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    loading: false,
    mode: 'create',
    form: () => ({
        external_id: '',
        name: '',
        telegram: '',
        contact: '',
    }),
    errors: () => ({} as ClientFormErrors),
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:form', value: ClientForm): void
    (e: 'submit'): void
    (e: 'close'): void
}>()

const form = computed({
    get: () => props.form,
    set: (value: ClientForm) => emit('update:form', value),
})

const { __ } = vueLang()
const fieldErrors = computed<ClientFormErrors>(() => props.errors ?? {})

const modalTitle = computed(() =>
    props.mode === 'edit'
        ? __('frontend.clients.modals.edit.title')
        : __('frontend.clients.modals.create.title')
)
const modalDescription = computed(() =>
    props.mode === 'edit'
        ? __('frontend.clients.modals.edit.description')
        : __('frontend.clients.modals.create.description')
)

function close() {
    emit('update:modelValue', false)
    emit('close')
}

function submit() {
    emit('submit')
}
</script>

<template>
    <ModalDialog
        :model-value="modelValue"
        :title="modalTitle"
        :description="modalDescription"
        size="lg"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <form class="mt-4 grid gap-4" @submit.prevent="submit">
            <FormControl :error="fieldErrors.external_id">
                <Label for="client-external-id" required>{{ __('frontend.clients.fields.external_id') }}</Label>
                <Input
                    id="client-external-id"
                    v-model="form.external_id"
                    type="text"
                    :placeholder="__('frontend.clients.fields.external_id_placeholder')"
                    required
                />
            </FormControl>

            <FormControl :error="fieldErrors.name">
                <Label for="client-name">{{ __('frontend.clients.fields.name') }}</Label>
                <Input
                    id="client-name"
                    v-model="form.name"
                    type="text"
                    :placeholder="__('frontend.clients.fields.name_placeholder')"
                />
            </FormControl>

            <FormControl :error="fieldErrors.telegram">
                <Label for="client-telegram">{{ __('frontend.clients.fields.telegram') }}</Label>
                <Input
                    id="client-telegram"
                    v-model="form.telegram"
                    type="text"
                    :placeholder="__('frontend.clients.fields.telegram_placeholder')"
                />
            </FormControl>

            <FormControl :error="fieldErrors.contact">
                <Label for="client-contact">{{ __('frontend.clients.fields.contact') }}</Label>
                <Textarea
                    id="client-contact"
                    v-model="form.contact"
                    rows="2"
                    :placeholder="__('frontend.clients.fields.contact_placeholder')"
                />
            </FormControl>
        </form>

        <template #actions>
            <button type="button" class="btn" @click="close" :disabled="loading">{{ __('frontend.common.cancel') }}</button>
            <button type="submit" class="btn btn-primary" :class="{ loading }" :disabled="loading" @click="submit">
                {{ props.mode === 'edit' ? __('frontend.clients.actions.save') : __('frontend.clients.actions.add') }}
            </button>
        </template>
    </ModalDialog>
</template>


