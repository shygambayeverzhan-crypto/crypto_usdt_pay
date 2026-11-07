<script setup lang="ts">
import ModalDialog from '@/components/ui/modal/ModalDialog.vue'
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue'
import Alert from '@/components/ui/Alert.vue'
import UidCopy from '@/components/ui/UidCopy.vue';
import LinkCopy from '@/components/ui/LinkCopy.vue';
import { vueLang } from '@erag/lang-sync-inertia';

type CallbackLog = {
    id: string
    invoice_id: string
    event: string
    url: string
    request_payload: any
    response_status: number | null
    response_body: string | null
    error_message: string | null
    duration_ms: number | null
    created_at: string | null
}

interface Props {
    modelValue: boolean
    log: CallbackLog | null
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'close'): void
}>()

const { __ } = vueLang();

function close() {
    emit('update:modelValue', false)
    emit('close')
}

function toIso(input: string | null | undefined): string {
    if (!input) return ''
    if (typeof input !== 'string') return ''
    if (input.includes('T')) return input
    return `${input.replace(' ', 'T')}Z`
}
</script>

<template>
    <ModalDialog
        :model-value="modelValue"
        :title="__('frontend.callbacks.details.title')"
        :description="__('frontend.callbacks.details.description')"
        size="2xl"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <template v-if="log">
            <div class="grid gap-4">
                <div class="grid gap-3">
                    <div class="text-xs opacity-60">ID</div>
                    <div class="font-mono break-all">
                        <UidCopy :uid="log.id"/>
                    </div>
                </div>

                <div class="grid gap-2 md:grid-cols-2">
                    <div class="grid gap-1">
                        <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.invoice') }}</div>
                        <div class="font-mono break-all">
                            <UidCopy :uid="log.invoice_id"/>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.event') }}</div>
                        <div><span class="badge badge-outline">{{ log.event }}</span></div>
                    </div>
                    <div class="grid gap-1 md:col-span-2">
                        <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.url') }}</div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-outline">POST</span>
                            <span class="break-all font-mono">
                                <LinkCopy :url="log.url"/>
                            </span>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.http_status') }}</div>
                        <div>
                            <span
                                class="badge"
                                :class="{
                                    'badge-success': (log.response_status || 0) >= 200 && (log.response_status || 0) < 300,
                                    'badge-error': (log.response_status || 0) >= 400,
                                }"
                            >
                                {{ log.response_status ?? '—' }}
                            </span>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.duration') }}</div>
                        <div>{{ log.duration_ms ?? '—' }}</div>
                    </div>
                    <div class="grid gap-1">
                        <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.created_at') }}</div>
                        <div>
                            <DateTimeFormat short-year :value="toIso(log.created_at)" />
                        </div>
                    </div>
                </div>

                <div class="divider my-0"></div>

                <div class="grid gap-3">
                    <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.request') }}</div>
                    <div class="mockup-code">
                        <pre><code>{{ JSON.stringify(log.request_payload || {}, null, 2) }}</code></pre>
                    </div>
                </div>

                <div class="grid gap-3">
                    <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.response') }}</div>
                    <div class="mockup-code">
                        <pre><code>{{ log.response_body || '' }}</code></pre>
                    </div>
                </div>

                <div class="grid gap-3" v-if="log.error_message">
                    <div class="text-xs opacity-60">{{ __('frontend.callbacks.details.error') }}</div>
                    <Alert type="warning" :message="log.error_message" />
                </div>
            </div>
        </template>

        <template #actions>
            <button class="btn btn-ghost" @click="close">
                {{ __('frontend.callbacks.details.close') }}
            </button>
        </template>
    </ModalDialog>
</template>


