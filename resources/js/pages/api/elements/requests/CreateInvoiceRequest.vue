<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { vueLang } from '@erag/lang-sync-inertia'
import { usePage } from '@inertiajs/vue3'
import MerchantSelect from '@/components/merchants/MerchantSelect.vue'
import type { CurrencyAmountRule } from '@/utils/currencyAmount'
import { normalizeCurrencyAmountOnBlur, sanitizeCurrencyAmountInput } from '@/utils/currencyAmount'
import { createApiClient, pretty } from '@/pages/api/elements/requests/apiClient'

interface Props {
    apiKey: string
    apiBase: string
}

const props = defineProps<Props>()
const { __ } = vueLang()
const page = usePage()

type MerchantOption = {
    value: string | number
    label: string
    initials?: string | null
    logo_url?: string | null
    description?: string | null
}

const currencyAmountRules = computed<Record<string, CurrencyAmountRule>>(
    () => (page.props.currencyAmountRules as any) ?? {},
)

const tabs = reactive({ view: 'form' as 'form' | 'example' })

const createForm = reactive({
    currency: 'usdt',
    network: 'tron',
    amount: '',
    external_invoice_id: '',
    callback_url: '',
    tag: '',
    merchant_id: '',
    client_id: '',
    product_name: '',
    product_description: '',
    metadata: '',
})

const createAmountRule = computed<CurrencyAmountRule>(() => {
    const currency = (createForm.currency || '').toString().toUpperCase()
    return currencyAmountRules.value[currency] ?? { decimals: 6, example: '12.123456', decimal_separator: '.' }
})

watch(
    () => createForm.amount,
    (value) => {
        const decimals = Number(createAmountRule.value.decimals ?? 0)
        const sanitized = sanitizeCurrencyAmountInput(String(value ?? ''), decimals)
        if (sanitized !== value) {
            createForm.amount = sanitized
        }
    },
)

watch(
    () => createForm.currency,
    () => {
        const decimals = Number(createAmountRule.value.decimals ?? 0)
        const sanitized = sanitizeCurrencyAmountInput(String(createForm.amount ?? ''), decimals)
        if (sanitized !== createForm.amount) {
            createForm.amount = sanitized
        }
    },
)

const createResult = ref<string>('')
const merchants = ref<MerchantOption[]>([])
const merchantsLoading = ref(false)
const merchantsError = ref<string>('')

const curlPayload = `{
  "currency": "usdt",
  "network": "tron",
  "amount": "12.34",
  "client_id": "customer-123",
  "external_invoice_id": "order-1"
}`

async function loadMerchants() {
    const { requestJson } = createApiClient(props.apiBase, props.apiKey)

    merchantsLoading.value = true
    merchantsError.value = ''

    const res = await requestJson('/merchants')
    merchantsLoading.value = false

    if (!res.ok) {
        merchants.value = []
        merchantsError.value = __('frontend.api.requests.merchants_load_failed')
        return
    }

    const list = Array.isArray(res.body)
        ? res.body
        : Array.isArray((res.body as any)?.merchants)
            ? (res.body as any).merchants
            : []

    merchants.value = list.map((merchant: any) => ({
        value: merchant.id,
        label: merchant.name,
        initials: merchant.initials ?? null,
        logo_url: merchant.logo_url ?? null,
        description: merchant.description ?? '',
    }))
}

onMounted(loadMerchants)

async function createInvoice() {
    const { requestJson } = createApiClient(props.apiBase, props.apiKey)

    let metadata: unknown = undefined
    if (createForm.metadata) {
        try { metadata = JSON.parse(createForm.metadata) } catch { metadata = undefined }
    }

    const payload: Record<string, unknown> = {
        currency: createForm.currency,
        network: createForm.network,
        amount: createForm.amount,
        external_invoice_id: createForm.external_invoice_id || undefined,
        callback_url: createForm.callback_url || undefined,
        tag: createForm.tag || undefined,
        merchant_id: createForm.merchant_id || undefined,
        client_id: createForm.client_id || undefined,
        product_name: createForm.product_name || undefined,
        product_description: createForm.product_description || undefined,
        metadata: metadata,
    }

    const res = await requestJson('/invoices', { method: 'POST', body: JSON.stringify(payload) })
    createResult.value = pretty({ status: res.status, body: res.body })
}
</script>

<template>
    <div class="collapse collapse-arrow bg-base-100 border">
        <input type="checkbox" />
        <div class="collapse-title text-md font-medium">{{ __('frontend.api.requests.create_title') }}</div>
        <div class="collapse-content space-y-4">
            <div class="w-fit">
                <div role="tablist" class="tabs tabs-box">
                    <a role="tab" :class="tabs.view === 'form' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'form'">{{ __('frontend.api.requests.tabs.form') }}</a>
                    <a role="tab" :class="tabs.view === 'example' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'example'">{{ __('frontend.api.requests.tabs.example') }}</a>
                </div>
            </div>
            <p class="text-sm text-base-content/70">{{ __('frontend.api.requests.create_description') }}</p>

            <div v-if="tabs.view === 'form'" class="grid md:grid-cols-3 gap-4">
                <div class="card-body gap-4 p-0 pt-3 w-full">
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.currency') }}</span>
                        <input class="input input-md w-full" v-model="createForm.currency" placeholder="usdt" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.network') }}</span>
                        <input class="input input-md w-full" v-model="createForm.network" placeholder="tron" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.amount') }}</span>
                        <input
                            class="input input-md w-full"
                            v-model="createForm.amount"
                            inputmode="decimal"
                            :placeholder="createAmountRule.example || '12.50'"
                            @blur="createForm.amount = normalizeCurrencyAmountOnBlur(createForm.amount)"
                        />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.external_id') }}</span>
                        <input class="input input-md w-full" v-model="createForm.external_invoice_id" placeholder="order-123" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.callback_url') }}</span>
                        <input class="input input-md w-full" v-model="createForm.callback_url" placeholder="https://example.com/callback" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.tag') }}</span>
                        <input class="input input-md w-full" v-model="createForm.tag" placeholder="vip" />
                    </label>
                    <div class="space-y-2">
                        <span class="text-sm font-medium">{{ __('frontend.api.requests.fields.merchant') }}</span>
                        <MerchantSelect
                            v-model="createForm.merchant_id"
                            :options="merchants"
                            :placeholder="__('frontend.invoices.fields.merchant_placeholder')"
                            :empty-label="__('frontend.invoices.fields.merchant_empty')"
                            :disabled="merchantsLoading"
                        />
                        <p v-if="merchantsError" class="text-error text-sm">{{ merchantsError }}</p>
                    </div>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.product_name') }}</span>
                        <input
                            class="input input-md w-full"
                            v-model="createForm.product_name"
                            :placeholder="__('frontend.invoices.fields.product_name_placeholder')"
                        />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.product_description') }}</span>
                        <textarea
                            class="textarea textarea-md textarea-bordered w-full"
                            v-model="createForm.product_description"
                            rows="3"
                            :placeholder="__('frontend.invoices.fields.product_description_placeholder')"
                        ></textarea>
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.metadata') }}</span>
                        <textarea class="textarea textarea-md textarea-bordered w-full" v-model="createForm.metadata" rows="4" placeholder='{"note":"vip"}'></textarea>
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.client_id') }}</span>
                        <input class="input input-md w-full" v-model="createForm.client_id" placeholder="customer-123" />
                    </label>
                    <div class="card-actions items-center gap-6">
                        <button class="btn btn-primary" @click="createInvoice">{{ __('frontend.api.requests.send') }}</button>
                    </div>
                </div>
                <div class="min-w-0 w-full sm:col-span-1 md:col-span-2">
                    <label class="label"><span class="label-text">{{ __('frontend.api.requests.response') }}</span></label>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full"><code class="block">{{ createResult }}</code></pre>
                </div>
            </div>

            <div v-else class="space-y-3 text-sm">
                <div class="bg-base-200 rounded-box p-3">
                    <p class="font-semibold">Headers:</p>
                    <ul class="list-disc list-inside">
                        <li>Accept: application/json</li>
                        <li>Content-Type: application/json</li>
                        <li>X-Api-Key: &lt;PUBLIC_API_KEY&gt;</li>
                    </ul>
                </div>
                <div class="bg-base-200 rounded-box p-3">
                    <p class="font-semibold">Parameters (body)</p>
                    <ul class="list-disc list-inside">
                        <li>currency (string, required)</li>
                        <li>network (string, required)</li>
                        <li>amount (string, required)</li>
                        <li>client_id, merchant_id</li>
                        <li>external_invoice_id, tag</li>
                        <li>callback_url</li>
                        <li>product_name, product_description</li>
                        <li>metadata (JSON object)</li>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold mb-1">Curl request example</p>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full pl-4"><code class="block">{{ `curl -X POST '${props.apiBase}/invoices' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: <PUBLIC_API_KEY>' \
  -d '${curlPayload}'` }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</template>


