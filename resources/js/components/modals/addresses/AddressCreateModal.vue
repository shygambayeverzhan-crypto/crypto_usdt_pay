<script setup lang="ts">
import { computed } from 'vue'
import ModalDialog from '@/components/ui/modal/ModalDialog.vue'
import FormControl from '@/components/form/FormControl.vue'
import Label from '@/components/form/Label.vue'
import Input from '@/components/form/Input.vue'
import CurrencyNetworkSelect, { type CurrencyNetworkOption } from '@/components/ui/CurrencyNetworkSelect.vue'
import { vueLang } from '@erag/lang-sync-inertia'

export interface AddressCreateForm {
    currency: string
    network: string
    address: string
}

type AddressCreateErrors = Partial<Record<keyof AddressCreateForm, string>>

interface Props {
    modelValue: boolean
    form: AddressCreateForm
    currencyNetworkOptions: CurrencyNetworkOption[]
    errors: AddressCreateErrors | null
    loading: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    loading: false,
    form: () => ({
        currency: '',
        network: '',
        address: '',
    }),
    currencyNetworkOptions: () => [],
    errors: () => ({} as AddressCreateErrors),
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:form', value: AddressCreateForm): void
    (e: 'submit'): void
    (e: 'close'): void
}>()

const form = computed({
    get: () => props.form,
    set: (value: AddressCreateForm) => emit('update:form', value),
})

const fieldErrors = computed<AddressCreateErrors>(() => props.errors ?? {})
const currencyNetworkError = computed(() => fieldErrors.value.currency || fieldErrors.value.network)

const { __ } = vueLang()

const currencyNetworkValue = computed<string | null>({
    get: () => {
        const currency = (form.value.currency || '').toString().trim()
        const network = (form.value.network || '').toString().trim()
        if (!currency || !network) return null
        return `${currency}|${network}`
    },
    set: (value) => {
        const raw = (value || '').toString()
        if (!raw) {
            form.value.currency = ''
            form.value.network = ''
            return
        }
        const [currency, network] = raw.split('|')
        form.value.currency = (currency || '').toString().trim()
        form.value.network = (network || '').toString().trim()
    },
})

function close() {
    emit('update:modelValue', false)
    emit('close')
}

function submit() {
    const trimmedForm = {
        ...form.value,
        address: form.value.address.trim(),
    }
    emit('update:form', trimmedForm)
    emit('submit')
}
</script>

<template>
    <ModalDialog
        :model-value="modelValue"
        :title="__('frontend.addresses.modals.create.title')"
        :description="__('frontend.addresses.modals.create.description')"
        size="xl"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <form class="mt-4 grid gap-4" @submit.prevent="submit">
            <FormControl :error="currencyNetworkError">
                <Label for="currency_network" required>{{ __('frontend.addresses.fields.currency_network') }}</Label>
                <CurrencyNetworkSelect
                    id="currency_network"
                    v-model="currencyNetworkValue"
                    :options="currencyNetworkOptions"
                    :placeholder="__('frontend.addresses.fields.currency_network_placeholder')"
                    :empty-label="__('frontend.addresses.fields.currency_network_placeholder')"
                    :disabled="loading"
                />
            </FormControl>
            <FormControl :error="fieldErrors.address">
                <Label for="address" required>{{ __('frontend.addresses.fields.address') }}</Label>
                <Input
                    id="address"
                    v-model="form.address"
                    type="text"
                    :placeholder="__('frontend.addresses.fields.address_placeholder')"
                    required
                />
            </FormControl>

            <div class="modal-action">
                <button type="button" class="btn" @click="close" :disabled="loading">{{ __('frontend.common.cancel') }}</button>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                    <span v-if="loading" class="loading loading-spinner loading-sm mr-2" />
                    {{ __('frontend.addresses.actions.add') }}
                </button>
            </div>
        </form>
    </ModalDialog>
</template>

