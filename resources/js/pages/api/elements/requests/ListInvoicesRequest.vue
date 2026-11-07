<script setup lang="ts">
import { reactive, ref } from 'vue'
import { vueLang } from '@erag/lang-sync-inertia'
import { buildQuery, createApiClient, pretty } from '@/pages/api/elements/requests/apiClient'

interface Props {
    apiKey: string
    apiBase: string
}

const props = defineProps<Props>()
const { __ } = vueLang()

const tabs = reactive({ view: 'form' as 'form' | 'example' })

const listForm = reactive({
    search: '',
    status: '',
    currency: '',
    network: '',
    merchant_id: '',
    client_id: '',
    external_invoice_id: '',
    tag: '',
    has_callback: false,
    page: 1,
    per_page: 20,
})

const listResult = ref<string>('')

const curlQueryParams = '?status=pending&client_id=customer-123&per_page=20'

async function listInvoices() {
    const { requestJson } = createApiClient(props.apiBase, props.apiKey)
    const res = await requestJson(`/invoices${buildQuery(listForm)}`)
    listResult.value = pretty({ status: res.status, body: res.body })
}
</script>

<template>
    <div class="collapse collapse-arrow bg-base-100 border">
        <input type="checkbox" />
        <div class="collapse-title text-md font-medium">{{ __('frontend.api.requests.list_title') }}</div>
        <div class="collapse-content space-y-4">
            <p class="text-sm text-base-content/70">{{ __('frontend.api.requests.list_description') }}</p>
            <div class="w-fit">
                <div role="tablist" class="tabs tabs-box">
                    <a role="tab" :class="tabs.view === 'form' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'form'">{{ __('frontend.api.requests.tabs.form') }}</a>
                    <a role="tab" :class="tabs.view === 'example' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'example'">{{ __('frontend.api.requests.tabs.example') }}</a>
                </div>
            </div>

            <div v-if="tabs.view === 'form'" class="grid md:grid-cols-3 gap-4">
                <div class="card-body gap-4 p-0 pt-3 w-full">
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.search') }}</span>
                        <input class="input input-md w-full" v-model="listForm.search" :placeholder="__('frontend.invoices_page.filters.search_placeholder')" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.status') }}</span>
                        <input class="input input-md w-full" v-model="listForm.status" placeholder="pending" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.currency') }}</span>
                        <input class="input input-md w-full" v-model="listForm.currency" placeholder="usdt" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.network') }}</span>
                        <input class="input input-md w-full" v-model="listForm.network" placeholder="tron" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.merchant_id') }}</span>
                        <input class="input input-md w-full" v-model="listForm.merchant_id" placeholder="1" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.client_id') }}</span>
                        <input class="input input-md w-full" v-model="listForm.client_id" placeholder="customer-123" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.external_id') }}</span>
                        <input class="input input-md w-full" v-model="listForm.external_invoice_id" placeholder="order-123" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.tag') }}</span>
                        <input class="input input-md w-full" v-model="listForm.tag" placeholder="vip" />
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="checkbox checkbox-primary" v-model="listForm.has_callback" />
                        <span class="label-text">{{ __('frontend.api.requests.fields.has_callback') }}</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="floating-label">
                            <span>{{ __('frontend.api.requests.fields.page') }}</span>
                            <input class="input input-md w-full" v-model.number="listForm.page" type="number" min="1" />
                        </label>
                        <label class="floating-label">
                            <span>{{ __('frontend.api.requests.fields.per_page') }}</span>
                            <input class="input input-md w-full" v-model.number="listForm.per_page" type="number" min="1" max="100" />
                        </label>
                    </div>
                    <div class="card-actions items-center gap-6">
                        <button class="btn btn-primary" @click="listInvoices">{{ __('frontend.api.requests.list_send') }}</button>
                    </div>
                </div>
                <div class="min-w-0 w-full sm:col-span-1 md:col-span-2">
                    <label class="label"><span class="label-text">{{ __('frontend.api.requests.response') }}</span></label>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full"><code class="block">{{ listResult }}</code></pre>
                </div>
            </div>

            <div v-else class="space-y-3 text-sm">
                <div class="bg-base-200 rounded-box p-3">
                    <p class="font-semibold">Headers:</p>
                    <ul class="list-disc list-inside">
                        <li>Accept: application/json</li>
                        <li>X-Api-Key: &lt;PUBLIC_API_KEY&gt;</li>
                    </ul>
                </div>
                <div class="bg-base-200 rounded-box p-3">
                    <p class="font-semibold">Query Parameters:</p>
                    <ul class="list-disc list-inside">
                        <li>status, currency, network</li>
                        <li>merchant_id, client_id</li>
                        <li>external_invoice_id, tag, search</li>
                        <li>has_callback (1), page (≥1), per_page (1..100)</li>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold mb-1">Curl request example</p>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full pl-4"><code class="block">{{ `curl -X GET '${props.apiBase}/invoices${curlQueryParams}' \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <PUBLIC_API_KEY>'` }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</template>


