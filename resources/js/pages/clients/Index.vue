<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import FilterPanel from '@/components/filters/FilterPanel.vue'
import Pagination from '@/components/ui/Pagination.vue'
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue'
import UidCopy from '@/components/ui/UidCopy.vue'
import ClientModal, { type ClientForm } from '@/components/modals/clients/ClientModal.vue'
import { vueLang } from '@erag/lang-sync-inertia'
import EditButton from '@/components/ui/table-actions/EditButton.vue'
import NavigationLinkButton from '@/components/ui/table-actions/NavigationLinkButton.vue'

interface ClientItem {
    id: string
    external_id: string
    name: string | null
    telegram: string | null
    contact: string | null
    created_at: string | null
}

interface PaginationLinks {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedClients {
    data: ClientItem[]
    links: PaginationLinks[]
}

interface ClientFilters {
    search: string
    has_contact: boolean
}

type FilterFieldType = 'text' | 'select' | 'checkpoint'
type FilterField = {
    key: keyof ClientFilters
    type: FilterFieldType
    label: string
    placeholder?: string
}

interface Props {
    clients: PaginatedClients
    filters?: Partial<ClientFilters>
}

const props = defineProps<Props>()
const { __ } = vueLang()

const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const editingId = ref<string | null>(null)

const form = useForm<ClientForm>({
    external_id: '',
    name: '',
    telegram: '',
    contact: '',
})

const filterDefaults: ClientFilters = {
    search: '',
    has_contact: false,
}
const filters = useForm<ClientFilters>({
    ...filterDefaults,
    ...(props.filters ?? {}),
})

const filtersModel = computed({
    get: () => ({
        ...filterDefaults,
        ...filters.data(),
    }),
    set: (value: ClientFilters) => {
        filters.search = value.search ?? ''
        filters.has_contact = Boolean(value.has_contact)
    },
})

const filterFields = computed<FilterField[]>(() => [
    {
        key: 'search',
        type: 'text',
        label: __('frontend.clients.filters.search'),
        placeholder: __('frontend.clients.filters.search_placeholder'),
    },
    {
        key: 'has_contact',
        type: 'checkpoint',
        label: __('frontend.clients.filters.has_contact'),
    },
])

function buildFilterPayload(extra: Record<string, unknown> = {}) {
    const payload = {
        ...filters.data(),
        ...extra,
    }

    const cleaned: Record<string, unknown> = {}

    Object.entries(payload).forEach(([key, value]) => {
        if (key === 'page') {
            cleaned[key] = value
            return
        }

        if (typeof value === 'boolean') {
            if (value) {
                cleaned[key] = 1
            }
            return
        }

        if (value !== undefined && value !== null && String(value).length > 0) {
            cleaned[key] = value
        }
    })

    return cleaned
}

function applyFilters() {
    filters
        .transform(() => buildFilterPayload({ page: 1 }))
        .get('/clients', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => filters.transform(data => data),
        })
}

function resetFilters() {
    filters.search = filterDefaults.search
    filters.has_contact = filterDefaults.has_contact

    applyFilters()
}

function openCreate() {
    modalMode.value = 'create'
    editingId.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEdit(client: ClientItem) {
    modalMode.value = 'edit'
    editingId.value = client.id
    form.external_id = client.external_id
    form.name = client.name || ''
    form.telegram = client.telegram || ''
    form.contact = client.contact || ''
    form.clearErrors()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    form.clearErrors()
}

function invoicesLink(client: ClientItem): string {
    return `/invoices?${new URLSearchParams({ client_id: client.id }).toString()}`
}

function updateFormPayload(payload: ClientForm) {
    form.external_id = payload.external_id
    form.name = payload.name
    form.telegram = payload.telegram
    form.contact = payload.contact
}

function submit() {
    if (modalMode.value === 'edit' && editingId.value) {
        form.patch(`/clients/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
            },
            onError: () => {
                showModal.value = true
            },
        })
        return
    }

    form.post('/clients', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            form.reset()
        },
        onError: () => {
            showModal.value = true
        },
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: __('frontend.nav.dashboard'), href: '/' }, { title: __('frontend.nav.clients'), href: '/clients' }]">
        <template #header-actions>
            <div class="flex items-center gap-2">
                <button class="btn btn-primary btn-sm" @click="openCreate">
                    <span class="hidden sm:block">{{ __('frontend.clients.actions.create') }}</span>
                    <span class="block sm:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </span>
                </button>
            </div>
        </template>

        <div class="grid gap-6">
            <FilterPanel
                v-model="filtersModel"
                :fields="filterFields"
                :title="__('frontend.clients.filters.title')"
                :apply-label="__('frontend.clients.filters.apply')"
                :reset-label="__('frontend.clients.filters.reset')"
                :show-label="__('frontend.clients.filters.show')"
                :hide-label="__('frontend.clients.filters.hide')"
                :any-option-label="__('frontend.clients.filters.any')"
                :loading="filters.processing"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <div class="lg:card lg:bg-base-100 lg:shadow">
                <div class="lg:card-body">
                    <h2 class="hidden lg:block card-title">{{ __('frontend.clients.list.title') }}</h2>
                    <h2 class="lg:hidden card-title mb-3">{{ __('frontend.clients.list.title') }}</h2>

                    <!-- Desktop Table View (lg and above) -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('frontend.common.id') }}</th>
                                    <th>{{ __('frontend.clients.table.external_id') }}</th>
                                    <th>{{ __('frontend.clients.table.name') }}</th>
                                    <th>{{ __('frontend.clients.table.contacts') }}</th>
                                    <th>{{ __('frontend.common.created_at') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="client in clients.data" :key="client.id">
                                    <td class="font-mono text-xs">
                                        <UidCopy :uid="client.id" />
                                    </td>
                                    <td class="font-mono text-xs truncate">{{ client.external_id }}</td>
                                    <td class="text-sm">
                                        <span v-if="client.name">{{ client.name }}</span>
                                        <span v-else class="opacity-60">—</span>
                                    </td>
                                    <td class="text-sm">
                                        <div class="flex flex-col">
                                            <span v-if="client.telegram" class="truncate">@{{ client.telegram.replace('@', '') }}</span>
                                            <span v-if="client.contact" class="truncate text-xs text-base-content/70">{{ client.contact }}</span>
                                            <span v-if="!client.telegram && !client.contact" class="opacity-60">—</span>
                                        </div>
                                    </td>
                                    <td class="text-xs font-mono">
                                        <DateTimeFormat v-if="client.created_at" short-year :value="client.created_at" />
                                        <span v-else class="opacity-60">—</span>
                                    </td>
                                    <td class="flex items-center gap-2">
                                        <NavigationLinkButton :href="invoicesLink(client)" :title="__('frontend.clients.actions.view_invoices')">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </NavigationLinkButton>
                                        <EditButton :title="__('frontend.common.edit')" @click="openEdit(client)" />
                                    </td>
                                </tr>
                                <tr v-if="!clients.data.length">
                                    <td colspan="6" class="text-center text-sm opacity-70 py-6">
                                        {{ __('frontend.clients.table.empty') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View (sm to lg) -->
                    <div class="hidden sm:block lg:hidden space-y-3">
                        <div v-for="client in clients.data" :key="client.id" class="card bg-base-100 shadow-sm">
                            <div class="card-body p-4 space-y-3">
                                <!-- Header: UUID and Date -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs opacity-70">UUID:</span>
                                        <UidCopy :uid="client.id" />
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70" v-if="client.created_at">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 opacity-70">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <DateTimeFormat :value="client.created_at" short-year />
                                    </div>
                                </div>

                                <!-- Main Content -->
                                <div class="grid grid-cols-[auto_1fr_auto] gap-3 items-center">
                                    <div>
                                        <div class="font-semibold truncate">{{ client.name || '—' }}</div>
                                        <div class="font-mono text-xs text-base-content/70 truncate">
                                            {{ __('frontend.clients.table.external_id') }}: {{ client.external_id || '—' }}
                                        </div>
                                    </div>
                                    <div class="flex justify-center text-sm">
                                        <div>
                                            <div v-if="client.telegram" class="truncate">@{{ client.telegram.replace('@', '') }}</div>
                                            <div v-if="client.contact" class="truncate text-xs text-base-content/70">{{ client.contact }}</div>
                                            <div v-if="!client.telegram && !client.contact" class="opacity-60 text-xs">—</div>
                                        </div>
                                    </div>

                                    <div class="inline-flex items-center gap-2">
                                        <NavigationLinkButton :href="invoicesLink(client)" :title="__('frontend.clients.actions.view_invoices')">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </NavigationLinkButton>
                                        <EditButton :title="__('frontend.common.edit')" @click="openEdit(client)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!clients.data.length" class="text-center text-sm opacity-70 py-6">
                            {{ __('frontend.clients.table.empty') }}
                        </div>
                    </div>

                    <!-- Small Mobile Card View (below sm) -->
                    <div class="sm:hidden space-y-3">
                        <div v-for="client in clients.data" :key="client.id" class="card bg-base-100 shadow-sm">
                            <div class="card-body p-4 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs opacity-70">UUID:</span>
                                        <UidCopy :uid="client.id" />
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70" v-if="client.created_at">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 opacity-70">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <DateTimeFormat :value="client.created_at" short-year />
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="space-y-1">
                                        <div class="font-semibold text-sm truncate">{{ client.name || '—' }}</div>
                                        <div class="text-xs text-base-content/70">
                                            <span class="opacity-70">{{ __('frontend.clients.table.external_id') }}:</span>
                                            <span class="font-mono ml-1">{{ client.external_id || '—' }}</span>
                                        </div>

                                    </div>

                                    <div class="flex items-center gap-2">
                                        <NavigationLinkButton :href="invoicesLink(client)" :title="__('frontend.clients.actions.view_invoices')">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                        </NavigationLinkButton>
                                        <EditButton :title="__('frontend.common.edit')" @click="openEdit(client)" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="text-xs text-base-content/70 truncate">
                                        <span v-if="client.telegram">@{{ client.telegram.replace('@', '') }}</span>
                                        <span v-else class="opacity-60">—</span>
                                    </div>
                                    <div class="text-xs text-base-content/70 truncate">
                                        <span v-if="client.contact">{{ client.contact }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!clients.data.length" class="text-center text-sm opacity-70 py-6">
                            {{ __('frontend.clients.table.empty') }}
                        </div>
                    </div>

                    <Pagination :links="clients.links" />
                </div>
            </div>
        </div>

        <ClientModal
            v-model="showModal"
            :mode="modalMode"
            :form="form"
            :errors="form.errors"
            :loading="form.processing"
            @update:form="updateFormPayload"
            @submit="submit"
            @close="closeModal"
        />
    </AppLayout>
</template>


