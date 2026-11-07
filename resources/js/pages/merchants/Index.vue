<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
// @ts-ignore пока нет типов для пакета в TS проверке
import { vueLang } from '@erag/lang-sync-inertia';
import MerchantModal, { type MerchantForm } from '@/components/modals/merchants/MerchantModal.vue';
import Pagination from '@/components/ui/Pagination.vue';
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue';
import MerchantAvatar from '@/components/merchants/MerchantAvatar.vue';
import FilterPanel from '@/components/filters/FilterPanel.vue';
import EditButton from '@/components/ui/table-actions/EditButton.vue';

type FilterType = 'text' | 'select' | 'checkpoint';

interface FilterField {
    key: keyof MerchantFilters;
    type: FilterType;
    label: string;
    placeholder?: string;
    options?: { value: string | number; label: string }[];
}

interface Merchant {
    id: number;
    name: string;
    description: string | null;
    initials: string;
    logo_path: string | null;
    logo_url: string | null;
    back_url: string | null;
    white_label_enabled: boolean;
    invoice_expires_in_minutes: number;
    created_at: string | null;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMerchants {
    data: Merchant[];
    links: PaginationLinks[];
}

interface MerchantFilters {
    search: string;
    white_label_enabled: boolean;
}

interface Props {
    merchants: PaginatedMerchants;
    filters?: Partial<MerchantFilters>;
}

const props = defineProps<Props>();
const { __ } = vueLang();

const showModal = ref(false);
const editing = ref<Merchant | null>(null);
const currentLogoUrl = ref<string | null>(null);

const merchantForm = useForm<MerchantForm>({
    name: '',
    description: '',
    initials: '',
    back_url: '',
    white_label_enabled: true,
    invoice_expires_in_minutes: 30,
    logo: null,
});
const filterDefaults: MerchantFilters = {
    search: '',
    white_label_enabled: false,
};
const filters = useForm<MerchantFilters>({
    ...filterDefaults,
    ...(props.filters ?? {}),
});
const filtersModel = computed({
    get: () => ({
        ...filterDefaults,
        ...filters.data(),
    }),
    set: (value: MerchantFilters) => {
        filters.search = value.search ?? '';
        filters.white_label_enabled = Boolean(value.white_label_enabled);
    },
});
const filterFields = computed<FilterField[]>(() => [
    {
        key: 'search',
        type: 'text',
        label: __('frontend.merchants.filters.search'),
        placeholder: __('frontend.merchants.filters.search_placeholder'),
    },
    {
        key: 'white_label_enabled',
        type: 'checkpoint',
        label: __('frontend.merchants.filters.white_label_enabled'),
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
        .get('/merchants', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => filters.transform(data => data),
        });
}

function resetFilters() {
    filters.search = filterDefaults.search;
    filters.white_label_enabled = filterDefaults.white_label_enabled;

    applyFilters();
}

function resetForm() {
    merchantForm.reset();
    merchantForm.clearErrors();
    merchantForm.logo = null;
    currentLogoUrl.value = null;
}

function openCreate() {
    editing.value = null;
    resetForm();
    showModal.value = true;
}

function openEdit(item: Merchant) {
    editing.value = item;
    merchantForm.name = item.name ?? '';
    merchantForm.description = item.description ?? '';
    merchantForm.initials = item.initials ?? '';
    merchantForm.back_url = item.back_url ?? '';
    merchantForm.white_label_enabled = item.white_label_enabled;
    merchantForm.invoice_expires_in_minutes = Number(item.invoice_expires_in_minutes ?? 30);
    merchantForm.logo = null;
    merchantForm.clearErrors();
    currentLogoUrl.value = item.logo_url;
    showModal.value = true;
}

function updateFormPayload(payload: MerchantForm) {
    merchantForm.name = payload.name;
    merchantForm.description = payload.description;
    merchantForm.initials = payload.initials;
    merchantForm.back_url = payload.back_url;
    merchantForm.white_label_enabled = payload.white_label_enabled;
    merchantForm.invoice_expires_in_minutes = payload.invoice_expires_in_minutes;
    merchantForm.logo = payload.logo;
}

function submit() {
    const isEdit = !!editing.value;
    const url = isEdit ? `/merchants/${editing.value?.id}` : '/merchants';
    const method = isEdit ? 'patch' : 'post';

    const payload = {
        name: (merchantForm.name ?? editing.value?.name ?? '').toString().trim(),
        description: (merchantForm.description ?? editing.value?.description ?? '').toString().trim(),
        initials: (merchantForm.initials ?? editing.value?.initials ?? '').toString().trim(),
        back_url: (merchantForm.back_url ?? editing.value?.back_url ?? '').toString().trim() || null,
        white_label_enabled: Boolean(merchantForm.white_label_enabled ?? editing.value?.white_label_enabled),
        invoice_expires_in_minutes: Number(
            merchantForm.invoice_expires_in_minutes ?? editing.value?.invoice_expires_in_minutes ?? 30
        ),
        logo: merchantForm.logo ?? null,
    };

    merchantForm.transform(() => payload)[method](url, {
        preserveScroll: true,
        forceFormData: !!payload.logo,
        onSuccess: () => {
            showModal.value = false;
            resetForm();
        },
        onError: () => {
            showModal.value = true;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: __('frontend.nav.dashboard'), href: '/' }, { title: __('frontend.nav.merchants'), href: '/merchants' }]">
        <template #header-actions>
            <div class="flex items-center gap-2">
                <button class="btn btn-primary btn-sm" @click="openCreate">
                    <span class="hidden sm:block">{{ __('frontend.merchants.actions.create') }}</span>
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
                :title="__('frontend.merchants.filters.title')"
                :apply-label="__('frontend.merchants.filters.apply')"
                :reset-label="__('frontend.merchants.filters.reset')"
                :show-label="__('frontend.merchants.filters.show')"
                :hide-label="__('frontend.merchants.filters.hide')"
                :any-option-label="__('frontend.merchants.filters.any')"
                :loading="filters.processing"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <div class="lg:card lg:bg-base-100 lg:shadow">
                <div class="lg:card-body">
                    <h2 class="hidden lg:block card-title">{{ __('frontend.merchants.list.title') }}</h2>
                    <h2 class="lg:hidden card-title mb-3">{{ __('frontend.merchants.list.title') }}</h2>

                    <div class="hidden lg:block overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('frontend.common.id') }}</th>
                                    <th>{{ __('frontend.merchants.fields.name') }}</th>
                                    <th>{{ __('frontend.merchants.fields.initials') }}</th>
                                    <th>{{ __('frontend.merchants.fields.description') }}</th>
                                    <th>{{ __('frontend.common.created_at') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="merchant in props.merchants.data" :key="merchant.id">
                                    <td class="whitespace-nowrap">#{{ merchant.id }}</td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <MerchantAvatar :name="merchant.name" :initials="merchant.initials" :logo-url="merchant.logo_url" size="md" />
                                            <div>
                                            <div class="flex items-center gap-1.5 font-semibold">
                                                <span>{{ merchant.name }}</span>
                                                <div
                                                    v-if="!merchant.white_label_enabled"
                                                    class="tooltip tooltip-top text-error"
                                                    :data-tip="__('frontend.merchants.fields.white_label_disabled_hint')"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ merchant.initials }}</td>
                                    <td class="max-w-xs truncate">{{ merchant.description }}</td>
                                    <td class="text-xs text-base-content/70 whitespace-nowrap">
                                        <DateTimeFormat short-year v-if="merchant.created_at" :value="merchant.created_at" />
                                        <span v-else>—</span>
                                    </td>
                                    <td class="text-right">
                                        <EditButton :title="__('frontend.common.edit')" @click="openEdit(merchant)" />
                                    </td>
                                </tr>
                                <tr v-if="props.merchants.data.length === 0">
                                    <td colspan="6" class="text-center text-sm opacity-70 py-6">{{ __('frontend.merchants.empty') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-3 lg:hidden">
                        <div v-for="merchant in props.merchants.data" :key="merchant.id" class="card bg-base-100 shadow-sm">
                            <div class="card-body p-4 space-y-3">
                                <div class="flex items-center gap-3">
                                    <MerchantAvatar :name="merchant.name" :initials="merchant.initials" :logo-url="merchant.logo_url" size="lg" />
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-1.5 font-semibold">
                                            <span>{{ merchant.name }}</span>
                                            <div
                                                v-if="!merchant.white_label_enabled"
                                                class="tooltip tooltip-left text-error"
                                                :data-tip="__('frontend.merchants.fields.white_label_disabled_hint')"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="text-xs text-base-content/70 truncate hidden sm:block max-w-xs">{{ merchant.description || '—' }}</div>
                                    </div>
                                    <div class="ml-auto">
                                        <EditButton :title="__('frontend.common.edit')" @click="openEdit(merchant)" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-base-content/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    <DateTimeFormat v-if="merchant.created_at" :value="merchant.created_at" short-year />
                                    <span v-else>—</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="!props.merchants.data.length" class="text-center text-sm opacity-70 py-6">
                            {{ __('frontend.merchants.empty') }}
                        </div>
                    </div>

                    <Pagination :links="props.merchants.links" />
                </div>
            </div>
        </div>

        <MerchantModal
            v-model="showModal"
            :form="merchantForm as unknown as MerchantForm"
            :errors="merchantForm.errors"
            :loading="merchantForm.processing"
            :mode="editing ? 'edit' : 'create'"
            :current-logo-url="currentLogoUrl"
            @update:form="updateFormPayload"
            @submit="submit"
            @close="resetForm"
        />
    </AppLayout>
</template>

