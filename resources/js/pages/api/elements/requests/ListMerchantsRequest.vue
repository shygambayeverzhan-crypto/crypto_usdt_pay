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

const merchantsForm = reactive({
    page: 1,
    per_page: 20,
})

const merchantsResult = ref<string>('')

async function loadMerchantsList() {
    const { requestJson } = createApiClient(props.apiBase, props.apiKey)
    const res = await requestJson(`/merchants${buildQuery(merchantsForm)}`)
    merchantsResult.value = pretty({ status: res.status, body: res.body })
}
</script>

<template>
    <div class="collapse collapse-arrow bg-base-100 border">
        <input type="checkbox" />
        <div class="collapse-title text-md font-medium">{{ __('frontend.api.requests.merchants_title') }}</div>
        <div class="collapse-content space-y-4">
            <p class="text-sm text-base-content/70">{{ __('frontend.api.requests.merchants_description') }}</p>
            <div class="w-fit">
                <div role="tablist" class="tabs tabs-box">
                    <a role="tab" :class="tabs.view === 'form' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'form'">{{ __('frontend.api.requests.tabs.form') }}</a>
                    <a role="tab" :class="tabs.view === 'example' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'example'">{{ __('frontend.api.requests.tabs.example') }}</a>
                </div>
            </div>

            <div v-if="tabs.view === 'form'" class="grid md:grid-cols-3 gap-4">
                <div class="card-body gap-4 p-0 pt-3 w-full">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="floating-label">
                            <span>{{ __('frontend.api.requests.fields.page') }}</span>
                            <input class="input input-md w-full" v-model.number="merchantsForm.page" type="number" min="1" />
                        </label>
                        <label class="floating-label">
                            <span>{{ __('frontend.api.requests.fields.per_page') }}</span>
                            <input class="input input-md w-full" v-model.number="merchantsForm.per_page" type="number" min="1" max="100" />
                        </label>
                    </div>
                    <div class="card-actions items-center gap-6">
                        <button class="btn btn-primary" @click="loadMerchantsList">{{ __('frontend.api.requests.request') }}</button>
                    </div>
                </div>
                <div class="min-w-0 w-full sm:col-span-1 md:col-span-2">
                    <label class="label"><span class="label-text">{{ __('frontend.api.requests.response') }}</span></label>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full"><code class="block">{{ merchantsResult }}</code></pre>
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
                        <li>page (number, ≥1)</li>
                        <li>per_page (number, 1..100)</li>
                    </ul>
                </div>
                <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full pl-4"><code class="block">{{ `curl -X GET '${props.apiBase}/merchants?page=1&per_page=20' \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <PUBLIC_API_KEY>'` }}</code></pre>
            </div>
        </div>
    </div>
</template>
