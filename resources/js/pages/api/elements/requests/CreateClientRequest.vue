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

const createClientForm = reactive({
    external_id: '',
    name: '',
    telegram: '',
    contact: '',
})

const createClientResult = ref<string>('')

async function createClientRequest() {
    const { requestJson } = createApiClient(props.apiBase, props.apiKey)

    const payload: Record<string, unknown> = {
        external_id: createClientForm.external_id || undefined,
        name: createClientForm.name || undefined,
        telegram: createClientForm.telegram || undefined,
        contact: createClientForm.contact || undefined,
    }

    const res = await requestJson('/clients', { method: 'POST', body: JSON.stringify(payload) })
    createClientResult.value = pretty({ status: res.status, body: res.body })
}
</script>

<template>
    <div class="collapse collapse-arrow bg-base-100 border">
        <input type="checkbox" />
        <div class="collapse-title text-md font-medium">{{ __('frontend.api.requests.client_create_title') }}</div>
        <div class="collapse-content space-y-4">
            <p class="text-sm text-base-content/70">{{ __('frontend.api.requests.client_create_description') }}</p>
            <div class="w-fit">
                <div role="tablist" class="tabs tabs-box">
                    <a role="tab" :class="tabs.view === 'form' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'form'">{{ __('frontend.api.requests.tabs.form') }}</a>
                    <a role="tab" :class="tabs.view === 'example' ? 'tab tab-active' : 'tab'" @click="tabs.view = 'example'">{{ __('frontend.api.requests.tabs.example') }}</a>
                </div>
            </div>

            <div v-if="tabs.view === 'form'" class="grid md:grid-cols-3 gap-4">
                <div class="card-body gap-4 p-0 pt-3 w-full">
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.external_client_id') }}</span>
                        <input class="input input-md w-full" v-model="createClientForm.external_id" placeholder="customer-123" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.client_name') }}</span>
                        <input class="input input-md w-full" v-model="createClientForm.name" :placeholder="__('frontend.clients.fields.name_placeholder')" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.client_telegram') }}</span>
                        <input class="input input-md w-full" v-model="createClientForm.telegram" placeholder="@customer" />
                    </label>
                    <label class="floating-label">
                        <span>{{ __('frontend.api.requests.fields.client_contact') }}</span>
                        <input class="input input-md w-full" v-model="createClientForm.contact" :placeholder="__('frontend.clients.fields.contact_placeholder')" />
                    </label>
                    <div class="card-actions items-center gap-6">
                        <button class="btn btn-primary" @click="createClientRequest">{{ __('frontend.api.requests.send') }}</button>
                    </div>
                </div>
                <div class="min-w-0 w-full sm:col-span-1 md:col-span-2">
                    <label class="label"><span class="label-text">{{ __('frontend.api.requests.response') }}</span></label>
                    <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full"><code class="block">{{ createClientResult }}</code></pre>
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
                        <li>external_id (string, required)</li>
                        <li>name (string, optional)</li>
                        <li>telegram (string, optional)</li>
                        <li>contact (string, optional)</li>
                    </ul>
                </div>
                <pre class="mockup-code whitespace-pre overflow-x-auto max-w-full w-full pl-4"><code class="block">{{ `curl -X POST '${props.apiBase}/clients' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: <PUBLIC_API_KEY>' \
  -d '{
    "external_id": "customer-123",
    "name": "John Doe",
    "telegram": "@johndoe",
    "contact": "john@example.com"
  }'` }}</code></pre>
            </div>
        </div>
    </div>
</template>


