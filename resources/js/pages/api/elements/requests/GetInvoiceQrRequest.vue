<script setup lang="ts">
import { reactive, ref } from 'vue'
import { vueLang } from '@erag/lang-sync-inertia'
import { createApiClient } from '@/pages/api/elements/requests/apiClient'

interface Props {
    apiKey: string
    apiBase: string
}

const props = defineProps<Props>()
const { __ } = vueLang()

const tabs = reactive({ view: 'form' as 'form' | 'example' })

const qrForm = reactive({ id: '' })
const qrUrl = ref<string>('')

async function getQr() {
    if (!qrForm.id) return

    const { requestBlob } = createApiClient(props.apiBase, props.apiKey)
    const res = await requestBlob(`/invoices/${encodeURIComponent(qrForm.id)}/qr`)

    if (res.ok) {
        const url = URL.createObjectURL(res.blob)
        qrUrl.value = url
        return
    }

    qrUrl.value = ''
}
</script>

<template>
    <div class="collapse collapse-arrow bg-base-100 border">
        <input type="checkbox" />
        <div class="collapse-title text-md font-medium">{{ __('frontend.api.requests.qr_title') }}</div>
        <div class="collapse-content space-y-4">
            <p class="text-sm text-base-content/70">{{ __('frontend.api.requests.qr_description') }}</p>
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
                        <input class="input input-md w-full" v-model="qrForm.id" placeholder="e.g. inv_123" />
                    </label>
                    <div class="card-actions items-center gap-6">
                        <button class="btn btn-primary" @click="getQr">{{ __('frontend.api.requests.request') }}</button>
                    </div>
                </div>
                <div class="min-w-0 w-full sm:col-span-1 md:col-span-2">
                    <label class="label"><span class="label-text">{{ __('frontend.api.requests.qr_preview') }}</span></label>
                    <div class="border rounded-box p-3 min-h-32 flex items-center justify-center bg-base-200">
                        <img v-if="qrUrl" :src="qrUrl" alt="QR" class="max-h-64" />
                        <span v-else class="text-base-content/60">{{ __('frontend.api.requests.qr_no_data') }}</span>
                    </div>
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
                    <p class="font-semibold">Parameters (path)</p>
                    <ul class="list-disc list-inside">
                        <li>id (string, required)</li>
                    </ul>
                </div>
                <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full pl-4"><code class="block">{{ `curl -X GET '${props.apiBase}/invoices/<id>/qr' \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <PUBLIC_API_KEY>' \
  -o qr.png` }}</code></pre>
            </div>
        </div>
    </div>
</template>


