<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import CurrencyNetworkBadge from '@/components/ui/CurrencyNetworkBadge.vue';
import type { CurrencyNetworkOption } from '@/components/ui/CurrencyNetworkSelect.vue';
import AddressCopy from '@/components/ui/AddressCopy.vue';
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue';
import Pagination from '@/components/ui/Pagination.vue';
import AddressCreateModal, { type AddressCreateForm } from '@/components/modals/addresses/AddressCreateModal.vue';
import FilterPanel from '@/components/filters/FilterPanel.vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface AddressItem {
    id: number;
    currency: string;
    currency_label: string;
    network: string;
    network_label: string;
    address: string;
    is_active: boolean;
    balance: string;
    last_checked_at: string | null;
    created_at: string | null;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedAddresses {
    data: AddressItem[];
    links: PaginationLinks[];
}

interface AddressFilters {
    search: string;
    currency: string;
    network: string;
    is_active: boolean;
}

type FilterType = 'text' | 'select' | 'checkpoint';

interface FilterField {
    key: string;
    type: FilterType;
    label: string;
    placeholder?: string;
    options?: { value: string; label: string }[];
}

interface Props {
    addresses: PaginatedAddresses;
    currencyOptions: { value: string; label: string }[];
    networkOptions: { value: string; label: string }[];
    currencyNetworkOptions: CurrencyNetworkOption[];
    filters?: Partial<AddressFilters>;
}

const props = defineProps<Props>();
const { __ } = vueLang();
const showCreate = ref(false);
const createForm = useForm<AddressCreateForm>({
    currency: '',
    network: '',
    address: '',
});
const filterDefaults: AddressFilters = {
    search: '',
    currency: '',
    network: '',
    is_active: false,
};
const filters = useForm<AddressFilters>({
    ...filterDefaults,
    ...(props.filters ?? {}),
});
const filtersModel = computed({
    get: () => ({
        ...filterDefaults,
        ...filters.data(),
    }),
    set: (value: AddressFilters) => {
        filters.search = value.search ?? '';
        filters.currency = value.currency ?? '';
        filters.network = value.network ?? '';
        filters.is_active = Boolean(value.is_active);
    },
});
const filterFields = computed<FilterField[]>(() => [
    {
        key: 'search',
        type: 'text',
        label: __('frontend.addresses.filters.search'),
        placeholder: __('frontend.addresses.filters.search_placeholder'),
    },
    {
        key: 'currency',
        type: 'select',
        label: __('frontend.addresses.filters.currency'),
        options: props.currencyOptions,
    },
    {
        key: 'network',
        type: 'select',
        label: __('frontend.addresses.filters.network'),
        options: props.networkOptions,
    },
    {
        key: 'is_active',
        type: 'checkpoint',
        label: __('frontend.addresses.filters.is_active'),
    },
]);

function buildFilterPayload(extra: Record<string, unknown> = {}) {
    const payload = {
        ...filters.data(),
        ...extra,
    };

    const cleaned: Record<string, unknown> = {};

    Object.entries(payload).forEach(([key, value]) => {
        if (key === 'page') {
            cleaned[key] = value;
            return;
        }

        if (typeof value === 'boolean') {
            if (value) {
                cleaned[key] = 1;
            }
            return;
        }

        if (value !== undefined && value !== null && String(value).length > 0) {
            cleaned[key] = value;
        }
    });

    return cleaned;
}

function applyFilters() {
    filters
        .transform(() => buildFilterPayload({ page: 1 }))
        .get('/addresses', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => filters.transform(data => data),
        });
}

function resetFilters() {
    filters.search = filterDefaults.search;
    filters.currency = filterDefaults.currency;
    filters.network = filterDefaults.network;
    filters.is_active = filterDefaults.is_active;

    applyFilters();
}

function closeCreate() {
    showCreate.value = false;
    createForm.clearErrors();
}

function updateCreatePayload(payload: AddressCreateForm) {
    createForm.currency = payload.currency;
    createForm.network = payload.network;
    createForm.address = payload.address;
}

function resetCreatePayload() {
    createForm.reset();
    createForm.clearErrors();
}

function submitCreate() {
    createForm.post('/addresses', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            resetCreatePayload();
        },
        onError: () => {
            showCreate.value = true;
        },
    });
}

function toggleAddress(id: number, nextActive: boolean) {
    const form = useForm({ is_active: nextActive });
    form.patch(`/addresses/${id}`, { preserveScroll: true });
}

function toIso(input: string | null | undefined): string {
    if (!input) return '';
    if (typeof input !== 'string') return '';
    if (input.includes('T')) return input; // уже ISO
    // Преобразуем 'YYYY-MM-DD HH:mm:ss' -> 'YYYY-MM-DDTHH:mm:ssZ'
    return `${input.replace(' ', 'T')}Z`;
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: __('frontend.nav.dashboard'), href: '/' }, { title: __('frontend.nav.addresses'), href: '/addresses' }]">
        <template #header-actions>
            <div class="flex items-center gap-2">
                <button class="btn btn-primary btn-sm" @click="showCreate = true">
                    <span class="hidden sm:block">{{ __('frontend.addresses.actions.create') }}</span>
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
                :title="__('frontend.addresses.filters.title')"
                :apply-label="__('frontend.addresses.filters.apply')"
                :reset-label="__('frontend.addresses.filters.reset')"
                :show-label="__('frontend.addresses.filters.show')"
                :hide-label="__('frontend.addresses.filters.hide')"
                :any-option-label="__('frontend.addresses.filters.any')"
                :loading="filters.processing"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <div class="lg:card lg:bg-base-100 lg:shadow">
                <div class="lg:card-body">
                    <h2 class="hidden lg:block card-title">{{ __('frontend.addresses.list.title') }}</h2>
                    <h2 class="lg:hidden card-title mb-3">{{ __('frontend.addresses.list.title') }}</h2>

                    <!-- Desktop Table View (lg and above) -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('frontend.common.id') }}</th>
                                    <th>{{ __('frontend.common.address') }}</th>
                                    <th>{{ __('frontend.common.balance') }}</th>
                                    <th>{{ __('frontend.common.currency') }}</th>
                                    <th>{{ __('frontend.common.active') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="addr in props.addresses.data" :key="addr.id">
                                    <td>{{ addr.id }}</td>
                                    <td class="font-mono text-xs">
                                        <AddressCopy :address="addr.address" />
                                    </td>
                                    <td>{{ addr.balance }}</td>
                                    <td>
                                        <CurrencyNetworkBadge
                                            :currency="addr.currency"
                                            :network="addr.network"
                                            :icon-size="22"
                                            :currency-label="addr.currency_label"
                                            :network-label="addr.network_label"
                                        />
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" class="toggle" :class="{'toggle-success': addr.is_active}" :checked="addr.is_active" @change="toggleAddress(addr.id, !addr.is_active)" />
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="props.addresses.data.length === 0">
                                    <td colspan="6" class="text-center text-sm opacity-70 py-6">{{ __('frontend.addresses.empty') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View (sm to lg) -->
                    <div class="hidden sm:block lg:hidden space-y-3">
                        <div v-for="addr in props.addresses.data" :key="addr.id" class="card bg-base-100 shadow-sm">
                            <div class="card-body p-4">
                                <!-- Header: ID and Date -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs opacity-70">{{ __('frontend.common.id') }}:</span>
                                        <span class="text-sm font-semibold">{{ addr.id }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs opacity-70" v-if="addr.created_at">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <DateTimeFormat short-year :value="toIso(addr.created_at)" />
                                    </div>
                                </div>

                                <!-- Main Content Row -->
                                <div class="grid grid-cols-[auto_1fr_1fr_auto] items-center gap-3">
                                    <div class="flex min-w-0">
                                        <div class="font-mono text-xs opacity-70 truncate">
                                            <AddressCopy :address="addr.address" />
                                        </div>
                                    </div>

                                    <div class="flex justify-center min-w-0">
                                        <CurrencyNetworkBadge :icon-size="22" :currency-label="addr.currency_label" :network-label="addr.network_label" />
                                    </div>

                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span class="text-xs opacity-70">{{ __('frontend.common.balance') }}:</span>
                                        <span class="text-sm font-semibold">{{ addr.balance }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" class="toggle toggle-sm" :class="{'toggle-success': addr.is_active}" :checked="addr.is_active" @change="toggleAddress(addr.id, !addr.is_active)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!props.addresses.data.length" class="text-center text-sm opacity-70 py-6">
                            {{ __('frontend.addresses.empty') }}
                        </div>
                    </div>

                    <!-- Small Mobile Card View (below sm) -->
                    <div class="sm:hidden space-y-3">
                        <div v-for="addr in props.addresses.data" :key="addr.id" class="card bg-base-100 shadow-sm">
                            <div class="card-body p-4">
                                <!-- Header: ID and Date -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs opacity-70">{{ __('frontend.common.id') }}:</span>
                                        <span class="text-sm font-semibold">{{ addr.id }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs opacity-70" v-if="addr.created_at">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <DateTimeFormat :value="toIso(addr.created_at)" short-year hide-seconds />
                                    </div>
                                </div>

                                <!-- Main Content Row -->
                                <div class="flex justify-between items-center gap-3">
                                    <!-- Balance and Currency -->
                                    <div class="flex items-center gap-2">
                                        <div class="font-mono text-xs text-center">
                                            <AddressCopy :address="addr.address" />
                                        </div>
                                    </div>

                                    <!-- Right Side: Active Toggle -->
                                    <div>
                                        <input type="checkbox" class="toggle toggle-sm" :class="{'toggle-success': addr.is_active}" :checked="addr.is_active" @change="toggleAddress(addr.id, !addr.is_active)" />
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-between mt-3">
                                    <div>
                                        <CurrencyNetworkBadge :icon-size="22" :currency-label="addr.currency_label" :network-label="addr.network_label" />
                                    </div>
                                    <div class="inline-flex items-center gap-2">
                                        <span class="text-xs opacity-70">{{ __('frontend.common.balance') }}:</span>
                                        <div class="text-sm font-semibold">{{ addr.balance }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!props.addresses.data.length" class="text-center text-sm opacity-70 py-6">
                            {{ __('frontend.addresses.empty') }}
                        </div>
                    </div>

                    <Pagination :links="props.addresses.links" />
                </div>
            </div>
        </div>

        <AddressCreateModal
            v-model="showCreate"
            :form="createForm"
            :currency-network-options="props.currencyNetworkOptions"
            :errors="createForm.errors"
            :loading="createForm.processing"
            @update:form="updateCreatePayload"
            @submit="submitCreate"
            @close="closeCreate"
        />
    </AppLayout>
</template>


