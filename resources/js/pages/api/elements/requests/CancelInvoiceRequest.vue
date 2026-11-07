<script setup lang="ts">
import { reactive, ref } from 'vue'
import { vueLang } from '@erag/lang-sync-inertia'
import { createApiClient, pretty } from '@/pages/api/elements/requests/apiClient'

interface Props {
    apiKey: string
    apiBase: string
}

const props = defineProps<Props>()
const { __ } = vueLang()

const tabs = reactive({ view: 'form' as 'form' | 'example' })

const cancelForm = reactive({ id: '' })
const cancelResult = ref<string>('')

async function cancelInvoice() {
    if (!cancelForm.id) return

    const { requestJson } = createApiClient(props.apiBase, props.apiKey)
    const res = await requestJson(`/invoices/${encodeURIComponent(cancelForm.id)}/cancel`, { method: 'POST' })
    cancelResult.value = pretty({ status: res.status, body: res.body })
}
</script>

<template>
    <div class="collapse collapse-arrow bg-base-100 border">
        <input type="checkbox" />
        <div class="collapse-title text-md font-medium">{{ __('frontend.api.requests.cancel_title') }}</div>
        <div class="collapse-content space-y-4">
            <p class="text-sm text-base-content/70">{{ __('frontend.api.requests.cancel_description') }}</p>
            <div class="w-fit">
                <div role="tablist" class="tabs tabs-box">
                    <a role="tab" :class="tabs.view === 'form' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'form'">{{ __('frontend.api.requests.tabs.form') }}</a>
                    <a role="tab" :class="tabs.view === 'example' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'example'">{{ __('frontend.api.requests.tabs.example') }}</a>
                </div>
            </div>

            <div v-if="tabs.view === 'form'" class="grid md:grid-cols-3 gap-4">
                <div class="card-body gap-4 p-0 pt-3 w-full">
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.invoice_id') }}</span>
                        <input class="input input-md w-full" v-model="cancelForm.id" placeholder="e.g. inv_123" />
                    </label>
                    <div class="card-actions items-center gap-6">
                        <button class="btn btn-warning" @click="cancelInvoice">{{ __('frontend.api.requests.cancel') }}</button>
                    </div>
                </div>
                <div class="min-w-0 w-full sm:col-span-1 md:col-span-2">
                    <label class="label"><span class="label-text">{{ __('frontend.api.requests.response') }}</span></label>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full"><code class="block">{{ cancelResult }}</code></pre>
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
                    <p class="font-semibold">Parameters (path)</p>
                    <ul class="list-disc list-inside">
                        <li>id (string, required)</li>
                    </ul>
                </div>
                <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full pl-4"><code class="block">{{ `curl -X POST '${props.apiBase}/invoices/<id>/cancel' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: <PUBLIC_API_KEY>'` }}</code></pre>
            </div>
        </div>
    </div>
</template>


