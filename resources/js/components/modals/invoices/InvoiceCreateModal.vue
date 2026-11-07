<script setup lang="ts">
import { computed, watch } from 'vue'
import ModalDialog from '@/components/ui/modal/ModalDialog.vue'
import FormControl from '@/components/form/FormControl.vue'
import FormInput from '@/components/form/Input.vue'
import Label from '@/components/form/Label.vue'
import CurrencyNetworkSelect, { type CurrencyNetworkOption } from '@/components/ui/CurrencyNetworkSelect.vue'
import Textarea from '@/components/form/Textarea.vue'
import MerchantSelect from '@/components/merchants/MerchantSelect.vue'
import ClientSelect from '@/components/clients/ClientSelect.vue'
import { vueLang } from '@erag/lang-sync-inertia'
import { Form, usePage } from '@inertiajs/vue3';
import type { CurrencyAmountRule } from '@/utils/currencyAmount'
import { normalizeCurrencyAmountOnBlur, sanitizeCurrencyAmountInput } from '@/utils/currencyAmount'

interface MerchantOption {
    value: string | number
    label: string
    initials?: string | null
    logo_url?: string | null
    description?: string | null
    disabled?: boolean
}
interface ClientOption {
    id: string
    value: string
    label: string
    contact?: string | null
    external_id?: string
}

export interface InvoiceCreateForm {
    currency: string
    network: string
    amount: string
    merchant_id: string | number | null
    client_id: string | null
    client_name: string
    client_telegram: string
    client_contact: string
    product_name: string
    product_description: string
    external_invoice_id: string
    callback_url: string
    tag: string
    metadata: string
}

type InvoiceCreateErrors = Partial<Record<keyof InvoiceCreateForm, string>>

interface Props {
    modelValue: boolean
    form: InvoiceCreateForm
    currencyNetworkOptions: CurrencyNetworkOption[]
    merchantOptions: MerchantOption[]
    clientOptions: ClientOption[]
    errors: InvoiceCreateErrors | null
    loading: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    loading: false,
    form: () => ({
        currency: '',
        network: '',
        amount: '',
        merchant_id: null,
        client_id: null,
        client_name: '',
        client_telegram: '',
        client_contact: '',
        product_name: '',
        product_description: '',
        external_invoice_id: '',
        callback_url: '',
        tag: '',
        metadata: '',
    }),
    currencyNetworkOptions: () => [],
    merchantOptions: () => [],
    clientOptions: () => [],
    errors: () => ({} as InvoiceCreateErrors),
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:form', value: InvoiceCreateForm): void
    (e: 'submit'): void
    (e: 'close'): void
    (e: 'create-client'): void
}>()

const form = computed({
    get: () => props.form,
    set: (value: InvoiceCreateForm) => emit('update:form', value),
})

const fieldErrors = computed<InvoiceCreateErrors>(() => props.errors ?? {})

const { __ } = vueLang()
const page = usePage()

const currencyAmountRules = computed<Record<string, CurrencyAmountRule>>(
    () => (page.props.currencyAmountRules as any) ?? {},
)

const currentAmountRule = computed<CurrencyAmountRule>(() => {
    const currency = (form.value.currency || '').toString().trim().toLowerCase()
    return currencyAmountRules.value[currency] ?? { decimals: 6, example: '12.123456', decimal_separator: '.' }
})

const amountPlaceholder = computed(() => {
    const currency = (form.value.currency || '').toString().toLowerCase()
    const rule = currencyAmountRules.value[currency]
    return rule?.example || __('frontend.invoices.fields.amount_placeholder')
})

const amountHint = computed(() => {
    const base = __('frontend.invoices.fields.amount_hint')
    const currency = (form.value.currency || '').toString().toLowerCase()
    if (!currency) return base

    const rule = currencyAmountRules.value[currency] ?? currentAmountRule.value
    const decimals = Number(rule.decimals ?? 0)
    const min = rule.min ?? null
    const max = rule.max ?? null

    const parts: string[] = []
    parts.push(`${currency}: до ${decimals} знаков после точки`)
    if (min !== null && max !== null) {
        parts.push(`мин: ${min}, макс: ${max}`)
    } else if (min !== null) {
        parts.push(`мин: ${min}`)
    } else if (max !== null) {
        parts.push(`макс: ${max}`)
    }

    return `${base} (${parts.join(', ')})`
})

const currencyNetworkValue = computed<string | null>({
    get: () => {
        const c = (form.value.currency || '').toString().trim()
        const n = (form.value.network || '').toString().trim()
        if (!c || !n) return null
        return `${c}|${n}`
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

const isCurrencySelected = computed(() => Boolean(currencyNetworkValue.value))

function onAmountInput(value: string | number) {
    // Если валюта не выбрана — поле должно быть неактивным, но на всякий случай
    // не даём сохранить/внести значение.
    if (!isCurrencySelected.value) {
        form.value.amount = ''
        return
    }
    const decimals = Number(currentAmountRule.value.decimals ?? 0)
    form.value.amount = sanitizeCurrencyAmountInput(String(value ?? ''), decimals)
}

function onAmountInputEvent(event: Event) {
    const target = event.target as HTMLInputElement

    if (!isCurrencySelected.value) {
        target.value = ''
        form.value.amount = ''
        return
    }

    const decimals = Number(currentAmountRule.value.decimals ?? 0)
    const sanitized = sanitizeCurrencyAmountInput(String(target.value ?? ''), decimals)

    // Ключевой момент: переписываем значение прямо в поле ввода,
    // чтобы пользователь физически не мог видеть/ввести "лишние" знаки.
    if (target.value !== sanitized) {
        target.value = sanitized
    }

    form.value.amount = sanitized
}

function onAmountBlur() {
    form.value.amount = normalizeCurrencyAmountOnBlur(form.value.amount)
}

function onAmountBlurEvent(event: FocusEvent) {
    const target = event.target as HTMLInputElement
    const normalized = normalizeCurrencyAmountOnBlur(String(target.value ?? ''))
    if (target.value !== normalized) {
        target.value = normalized
    }
    form.value.amount = normalized
}

watch(
    () => form.value.currency,
    (newCurrency, oldCurrency) => {
        // При смене валюты сумма должна сбрасываться
        if (String(newCurrency ?? '') !== String(oldCurrency ?? '')) {
            form.value.amount = ''
        }
    },
)

watch(
    () => form.value.amount,
    (value) => {
        // Дублирующая защита: если значение попало в модель не через инпут (вставка, автозаполнение и т.п.),
        // всё равно ограничиваем дробную часть по текущей валюте.
        if (!isCurrencySelected.value) return
        const decimals = Number(currentAmountRule.value.decimals ?? 0)
        const sanitized = sanitizeCurrencyAmountInput(String(value ?? ''), decimals)
        if (sanitized !== value) {
            form.value.amount = sanitized
        }
    },
)

function close() {
    emit('update:modelValue', false)
    emit('close')
}

function submit() {
    const trimmedForm = {
        ...form.value,
        amount: form.value.amount.trim(),
        client_id: form.value.client_id ? form.value.client_id.trim() : null,
        external_invoice_id: form.value.external_invoice_id.trim(),
        callback_url: form.value.callback_url.trim(),
        tag: form.value.tag.trim(),
    }
    emit('update:form', trimmedForm)
    emit('submit')
}
</script>

<template>
    <ModalDialog
        :model-value="modelValue"
        :title="__('frontend.invoices.modals.create.title')"
        :description="__('frontend.invoices.modals.create.description')"
        size="2xl"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <form class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-4" @submit.prevent="submit">
            <FormControl :error="fieldErrors.merchant_id">
                <Label for="merchant_id" required>{{ __('frontend.invoices.fields.merchant') }}</Label>
                <MerchantSelect
                    id="merchant_id"
                    v-model="form.merchant_id"
                    :options="merchantOptions"
                    :placeholder="__('frontend.invoices.fields.merchant_placeholder')"
                    :empty-label="__('frontend.invoices.fields.merchant_empty')"
                    :disabled="loading"
                />
            </FormControl>
            <FormControl :error="fieldErrors.client_id">
                <div class="flex items-center justify-between">
                    <Label for="client_id">{{ __('frontend.invoices.fields.client') }}</Label>
                    <button type="button" class="btn btn-ghost btn-xs" @click="emit('create-client')">
                        {{ __('frontend.invoices.fields.client_create') }}
                    </button>
                </div>
                <ClientSelect
                    id="client_id"
                    v-model="form.client_id"
                    :options="clientOptions"
                    :placeholder="__('frontend.invoices.fields.client_placeholder')"
                    :empty-label="__('frontend.invoices.fields.client_empty')"
                />
            </FormControl>
            <FormControl :error="fieldErrors.currency">
                <Label for="currency_network" required>{{ __('frontend.invoices.fields.currency_network') }}</Label>
                <CurrencyNetworkSelect
                    id="currency_network"
                    v-model="currencyNetworkValue"
                    :options="currencyNetworkOptions"
                    :placeholder="__('frontend.invoices.fields.currency_network_placeholder')"
                    :empty-label="__('frontend.invoices.fields.currency_network_placeholder')"
                    :disabled="loading"
                />
            </FormControl>
            <FormControl :error="fieldErrors.amount">
                <Label for="amount" required>{{ __('frontend.invoices.fields.amount') }}</Label>
                <input
                    id="amount"
                    class="input input-bordered w-full input-md"
                    type="text"
                    inputmode="decimal"
                    :value="form.amount"
                    :placeholder="amountPlaceholder"
                    :disabled="loading || !isCurrencySelected"
                    required
                    @input="onAmountInputEvent"
                    @blur="onAmountBlurEvent"
                />
            </FormControl>
            <FormControl :error="fieldErrors.external_invoice_id">
                <Label for="external_invoice_id">{{ __('frontend.invoices.fields.external_id') }}</Label>
                <FormInput
                    id="external_invoice_id"
                    v-model="form.external_invoice_id"
                    type="text"
                />
            </FormControl>
            <FormControl :error="fieldErrors.callback_url">
                <Label for="callback_url">{{ __('frontend.invoices.fields.callback_url') }}</Label>
                <FormInput
                    id="callback_url"
                    v-model="form.callback_url"
                    type="url"
                />
            </FormControl>
            <FormControl :error="fieldErrors.tag">
                <Label for="tag">{{ __('frontend.invoices.fields.tag') }}</Label>
                <FormInput
                    id="tag"
                    v-model="form.tag"
                    type="text"
                />
            </FormControl>
            <FormControl :error="fieldErrors.product_name">
                <Label for="product_name">{{ __('frontend.invoices.fields.product_name') }}</Label>
                <FormInput
                    id="product_name"
                    v-model="form.product_name"
                    type="text"
                    :placeholder="__('frontend.invoices.fields.product_name_placeholder')"
                />
            </FormControl>
            <FormControl :error="fieldErrors.product_description">
                <Label for="product_description">{{ __('frontend.invoices.fields.product_description') }}</Label>
                <Textarea
                    id="product_description"
                    v-model="form.product_description"
                    :placeholder="__('frontend.invoices.fields.product_description_placeholder')"
                    :rows="3"
                />
            </FormControl>
            <FormControl :error="fieldErrors.metadata">
                <Label for="metadata">{{ __('frontend.invoices.fields.metadata') }}</Label>
                <Textarea
                    id="metadata"
                    v-model="form.metadata"
                    :placeholder="__('frontend.invoices.fields.metadata_placeholder')"
                    :rows="4"
                />
            </FormControl>
        </form>

        <template #actions>
            <button type="button" class="btn" @click="close" :disabled="loading">{{ __('frontend.common.cancel') }}</button>
            <button type="button" class="btn btn-primary" :class="{ loading }" :disabled="loading" @click="submit">
                {{ __('frontend.invoices.actions.create') }}
            </button>
        </template>
    </ModalDialog>
</template>


