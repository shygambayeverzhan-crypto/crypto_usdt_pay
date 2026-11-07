<script setup lang="ts">
import ModalDialog from '@/components/ui/modal/ModalDialog.vue'
import CurrencyNetworkBadge from '@/components/ui/CurrencyNetworkBadge.vue'
import AddressCopy from '@/components/ui/AddressCopy.vue'
import UidCopy from '@/components/ui/UidCopy.vue'
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue'
import Alert from '@/components/ui/Alert.vue'
import TxidCopy from '@/components/ui/TxidCopy.vue';
import LinkCopy from '@/components/ui/LinkCopy.vue';
import { vueLang } from '@erag/lang-sync-inertia';

type Invoice = {
    id: string
    external_invoice_id: string | null
    address_id: number
    address?: string | null
    amount: number
    currency: string
    currency_label?: string | null
    network: string
    network_label?: string | null
    status: string
    txid: string | null
    tx_explorer_url?: string | null
    amount_received: number
    confirmations: number
    expires_at: string | null
    callback_url: string | null
    tag: string | null
    metadata: Record<string, any> | null
    client?: {
        id: string
        external_id: string
        name: string | null
    } | null
    client_external_id?: string | null
    created_at: string | null
}

interface StatusGroups {
    active: string[]
    final: string[]
}

interface Props {
    modelValue: boolean
    invoice: Invoice | null
    statuses: StatusGroups
    sendLoading: boolean
    sendError: string | null
    sendSuccess: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    statuses: () => ({ active: [], final: [] }),
    sendLoading: false,
    sendError: null,
    sendSuccess: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'edit'): void
    (e: 'send-callback'): void
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
        :title="__('frontend.invoices_details.title')"
        :description="__('frontend.invoices_details.description')"
        size="3xl"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <template v-if="invoice">
            <div class="space-y-6">
                <!-- Основная информация -->
                <div class="space-y-4">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <div class="text-xs text-base-content/60 mb-1">ID</div>
                            <div class="text-base">
                                <UidCopy v-if="invoice.id" :uid="invoice.id" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <div class="text-xs text-base-content/60 mb-1">{{ __('frontend.invoices_details.main.status') }}</div>
                            <div>
                                <span
                                    class="badge"
                                    :class="{
                                        'badge-warning': statuses.active.includes(invoice.status),
                                        'badge-success': invoice.status === 'paid',
                                        'badge-error': statuses.final.includes(invoice.status) && invoice.status !== 'paid',
                                    }"
                                >{{ invoice.status }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <div class="text-xs text-base-content/60 mb-1">{{ __('frontend.invoices_details.main.amount') }}</div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-lg font-semibold">{{ invoice.amount }}</span>
                                <CurrencyNetworkBadge
                                    :currency-label="invoice.currency_label || invoice.currency"
                                    :network-label="invoice.network_label || invoice.network"
                                />
                            </div>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <div class="text-xs text-base-content/60 mb-1">{{ __('frontend.invoices_details.main.address') }}</div>
                            <div>
                                <AddressCopy v-if="invoice.address" :address="invoice.address" />
                                <span v-else class="text-base-content/60">#{{ invoice.address_id }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Детали платежа -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-base-content/80">{{ __('frontend.invoices_details.payment.title') }}</h3>
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.main.client') }}</div>
                            <div class="flex-1 flex items-center gap-2 flex-wrap">
                                <span class="font-medium">{{ invoice.client?.name || invoice.client_external_id || '—' }}</span>
                                <span v-if="invoice.client?.external_id && invoice.client?.name" class="text-xs text-base-content/60 truncate">
                                    {{ invoice.client?.external_id }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.payment.external_id') }}</div>
                            <div class="flex-1 flex items-center gap-2 flex-wrap">
                                <UidCopy v-if="invoice.external_invoice_id" :uid="invoice.external_invoice_id" />
                                <span v-else class="text-base-content/60">—</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.payment.txid') }}</div>
                            <div class="flex-1 flex items-center gap-2 flex-wrap">
                                <TxidCopy v-if="invoice.txid" :txid="invoice.txid" />
                                <a
                                    v-if="invoice.status === 'paid' && invoice.tx_explorer_url"
                                    :href="invoice.tx_explorer_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-ghost btn-xs btn-circle"
                                    :title="__('frontend.invoices_details.payment.open_explorer')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                                <span v-if="!invoice.txid" class="text-base-content/60">—</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.payment.received_confirmations') }}</div>
                            <div class="flex-1">
                                <span class="font-mono">{{ invoice.amount_received }} / {{ invoice.confirmations }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.payment.expires_at') }}</div>
                            <div class="flex-1">
                                <DateTimeFormat short-year v-if="invoice.expires_at" :value="toIso(invoice.expires_at)" />
                                <span v-else class="text-base-content/60">—</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.payment.created_at') }}</div>
                            <div class="flex-1">
                                <DateTimeFormat short-year :value="toIso(invoice.created_at)" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Дополнительная информация -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-base-content/80">{{ __('frontend.invoices_details.extra.title') }}</h3>
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">{{ __('frontend.invoices_details.extra.callback_url') }}</div>
                            <div class="flex-1">
                                <div v-if="invoice.callback_url" class="flex items-center gap-2 flex-wrap">
                                    <span class="badge badge-outline badge-sm">POST</span>
                                    <span class="break-all font-mono text-sm">
                                        <LinkCopy :url="invoice.callback_url"/>
                                    </span>
                                </div>
                                <span v-else class="text-base-content/60">—</span>
                            </div>
                        </div>
<!--      Временно не отображаем -->
<!--                        <div class="grid grid-cols-1 gap-2">
                            <div class="text-xs text-base-content/60 sm:w-32 flex-shrink-0">Метаданные</div>
                            <div class="flex-1">
                                <div class="mockup-code text-xs">
                                    <pre><code>{{ JSON.stringify(invoice.metadata || {}, null, 2) }}</code></pre>
                                </div>
                            </div>
                        </div>-->
                    </div>
                </div>

                <!-- Уведомления -->
                <div class="space-y-2">
                    <Alert v-if="sendError" type="error" :message="sendError" />
                    <Alert v-if="sendSuccess" type="success" :message="__('frontend.invoices_details.notifications.callback_sent')" />
                </div>
            </div>
        </template>

        <template #actions>
            <button class="btn btn-ghost" @click="close">
                <span class="hidden sm:inline">{{ __('frontend.invoices_details.actions.close') }}</span>
                <span class="inline sm:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </span>
            </button>
            <button class="btn" :disabled="!invoice" @click="emit('edit')">
                <span class="hidden sm:inline">{{ __('frontend.invoices_details.actions.edit') }}</span>
                <span class="inline sm:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                </span>
            </button>
            <button
                v-if="invoice?.callback_url"
                class="btn btn-primary"
                :class="{ loading: sendLoading }"
                :disabled="sendLoading"
                @click="emit('send-callback')"
            >
                <span class="hidden sm:inline">{{ __('frontend.invoices_details.actions.send_callback') }}</span>
                <span class="inline sm:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                </span>
            </button>
        </template>
    </ModalDialog>
</template>


