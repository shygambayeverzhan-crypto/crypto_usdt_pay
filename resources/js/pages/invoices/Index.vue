<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import CurrencyNetworkBadge from '@/components/ui/CurrencyNetworkBadge.vue';
import AddressCopy from '@/components/ui/AddressCopy.vue';
import UidCopy from '@/components/ui/UidCopy.vue';
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue';
import Pagination from '@/components/ui/Pagination.vue';
import ViewButton from '@/components/ui/table-actions/ViewButton.vue';
import ExternalLinkButton from '@/components/ui/table-actions/ExternalLinkButton.vue';
import InvoiceDetailsModal from '@/components/modals/invoices/InvoiceDetailsModal.vue';
import InvoiceEditModal, { type InvoiceEditForm } from '@/components/modals/invoices/InvoiceEditModal.vue';
import InvoiceCreateModal, { type InvoiceCreateForm } from '@/components/modals/invoices/InvoiceCreateModal.vue';
import ClientModal, { type ClientForm } from '@/components/modals/clients/ClientModal.vue';
import FilterPanel from '@/components/filters/FilterPanel.vue';
import { vueLang } from '@erag/lang-sync-inertia';

type Invoice = {
  id: string
  external_invoice_id: string | null
  address_id: number
  amount: number
  currency: string
  network: string
  status: string
  txid: string | null
  tx_explorer_url?: string | null
  amount_received: number
  confirmations: number
  expires_at: string | null
  callback_url: string | null
  tag: string | null
  metadata: Record<string, any> | null
  client_id?: string | null
  client_external_id?: string | null
  client?: {
    id: string
    external_id: string
    name: string | null
    telegram: string | null
    contact: string | null
  } | null
  created_at: string | null
  updated_at: string | null
}

interface Option { value: string; label: string }
interface MerchantOption {
  value: string | number;
  label: string;
  initials?: string | null;
  logo_url?: string | null;
  description?: string | null;
}
interface ClientOption {
  id: string;
  value: string;
  label: string;
  contact?: string | null;
  external_id?: string;
}

interface InvoiceFilters {
  search: string;
  status: string;
  currency: string;
  network: string;
  merchant_id: string;
  client_id: string;
  has_callback: boolean;
}

type FilterFieldType = 'text' | 'select' | 'checkpoint';
type FilterField = {
  key: keyof InvoiceFilters;
  type: FilterFieldType;
  label: string;
  placeholder?: string;
  options?: { value: string | number; label: string }[];
};

const page = usePage();
const invoices = computed(() => page.props.invoices as any);
const statuses = computed(() => page.props.statuses as { active: string[]; final: string[] });
const currencyOptions = computed(() => page.props.currencyOptions as Option[]);
const networkOptions = computed(() => page.props.networkOptions as Option[]);
const currencyNetworkOptions = computed(() => (page.props.currencyNetworkOptions as any) ?? []);
const merchantOptions = computed(() => page.props.merchantOptions as MerchantOption[]);
const clientOptions = ref<ClientOption[]>(((page.props.clientOptions as any) ?? []) as ClientOption[]);
const clientFilterOptions = computed(() =>
  clientOptions.value.map((client) => ({
    value: client.id,
    label: client.label || client.value,
  })),
);
const pageFilters = computed(() => (page.props.filters as Partial<InvoiceFilters> | undefined) ?? {});
const { __ } = vueLang();

const selected: any = ref<Invoice | null>(null);
const showModal = ref(false);
const showCreate = ref(false);
const showEdit = ref(false);
const sendLoading = ref(false);
const sendError = ref<string | null>(null);
const sendSuccess = ref(false);
const showClientModal = ref(false);
const clientModalLoading = ref(false);
const clientModalErrors = ref<Partial<Record<keyof ClientForm, string>>>({});
const clientModalForm = ref<ClientForm>({
  external_id: '',
  name: '',
  telegram: '',
  contact: '',
});

const editForm = useForm<InvoiceEditForm>({ status: '', txid: null });
const filterDefaults: InvoiceFilters = {
  search: '',
  status: '',
  currency: '',
  network: '',
  merchant_id: '',
  client_id: '',
  has_callback: false,
};
const filters = useForm<InvoiceFilters>({
  ...filterDefaults,
  ...pageFilters.value,
});
const filtersModel = computed({
  get: () => ({
    ...filterDefaults,
    ...filters.data(),
  }),
  set: (value: InvoiceFilters) => {
    filters.search = value.search ?? '';
    filters.status = value.status ?? '';
    filters.currency = value.currency ?? '';
    filters.network = value.network ?? '';
    filters.merchant_id = value.merchant_id ?? '';
    filters.client_id = value.client_id ?? '';
    filters.has_callback = Boolean(value.has_callback);
  },
});
const filterFields = computed<FilterField[]>(() => [
  {
    key: 'search',
    type: 'text',
    label: __('frontend.invoices_page.filters.search'),
    placeholder: __('frontend.invoices_page.filters.search_placeholder'),
  },
  {
    key: 'status',
    type: 'select',
    label: __('frontend.invoices_page.filters.status'),
    options: allStatusOptions.value,
  },
  {
    key: 'currency',
    type: 'select',
    label: __('frontend.invoices_page.filters.currency'),
    options: currencyOptions.value,
  },
  {
    key: 'network',
    type: 'select',
    label: __('frontend.invoices_page.filters.network'),
    options: networkOptions.value,
  },
  {
    key: 'merchant_id',
    type: 'select',
    label: __('frontend.invoices_page.filters.merchant'),
    options: merchantOptions.value,
  },
  {
    key: 'client_id',
    type: 'select',
    label: __('frontend.invoices_page.filters.client'),
    options: clientFilterOptions.value,
  },
  {
    key: 'has_callback',
    type: 'checkpoint',
    label: __('frontend.invoices_page.filters.has_callback'),
  },
]);

function updateEditPayload(payload: InvoiceEditForm) {
  editForm.status = payload.status;
  editForm.txid = payload.txid;
}

const allStatusOptions = computed(() => {
  const a = statuses.value.active || [];
  const f = statuses.value.final || [];
  const merged = Array.from(new Set([...a, ...f]));
  return merged.map(s => ({ value: s, label: s }));
});

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
    .get('/invoices', {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      onFinish: () => {
        filters.transform(data => data);
      },
    });
}

function resetFilters() {
  filters.search = filterDefaults.search;
  filters.status = filterDefaults.status;
  filters.currency = filterDefaults.currency;
  filters.network = filterDefaults.network;
  filters.merchant_id = filterDefaults.merchant_id;
  filters.client_id = filterDefaults.client_id;
  filters.has_callback = filterDefaults.has_callback;

  applyFilters();
}

function openDetails(inv: Invoice) {
  selected.value = inv;
  showModal.value = true;
}

function closeDetails() {
  showModal.value = false;
}

function openEdit() {
  if (!selected.value) return;
  editForm.clearErrors();
  editForm.status = selected.value.status;
  editForm.txid = selected.value.txid || null;
  showEdit.value = true;
}

function closeEdit() {
  showEdit.value = false;
  editForm.clearErrors();
}

async function submitEdit() {
  if (!selected.value) return;
  editForm.patch(`/invoices/${selected.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEdit.value = false;
    },
    onError: () => {
      showEdit.value = true;
    },
  });
}

async function sendCallback() {
  if (!selected.value) return;
  if (!selected.value.callback_url) return;
  sendError.value = null;
  sendSuccess.value = false;
  sendLoading.value = true;
  try {
    await axios.post(`/invoices/${selected.value.id}/send-callback`);
    sendSuccess.value = true;
  } catch (e: any) {
    sendError.value = e?.response?.data?.message || e?.message || __('frontend.invoices_page.errors.callback_send');
  } finally {
    sendLoading.value = false;
  }
}

const createForm = useForm<InvoiceCreateForm>({
  currency: '',
  network: '',
  amount: '',
  merchant_id: '',
  client_id: '',
  client_name: '',
  client_telegram: '',
  client_contact: '',
  product_name: '',
  product_description: '',
  external_invoice_id: '',
  callback_url: '',
  tag: '',
  metadata: '',
});

function closeCreate() {
  showCreate.value = false;
  createForm.clearErrors();
}

function updateCreatePayload(payload: InvoiceCreateForm) {
  createForm.currency = payload.currency;
  createForm.network = payload.network;
  createForm.amount = payload.amount;
  createForm.merchant_id = payload.merchant_id;
  createForm.client_id = payload.client_id;
  createForm.client_name = payload.client_name;
  createForm.client_telegram = payload.client_telegram;
  createForm.client_contact = payload.client_contact;
  createForm.product_name = payload.product_name;
  createForm.product_description = payload.product_description;
  createForm.external_invoice_id = payload.external_invoice_id;
  createForm.callback_url = payload.callback_url;
  createForm.tag = payload.tag;
  createForm.metadata = payload.metadata;
}

function resetCreatePayload() {
  createForm.reset();
  createForm.clearErrors();
}

function submitCreate() {
  if (!createForm.merchant_id) {
    createForm.setError('merchant_id', __('frontend.invoices.fields.merchant_placeholder'));
    return;
  }

  let metadataParsed: Record<string, unknown> | undefined;
  if (createForm.metadata) {
    try {
      metadataParsed = JSON.parse(createForm.metadata as string);
    } catch (e) {
      createForm.setError('metadata', __('frontend.invoices_page.errors.metadata_invalid'));
      return;
    }
  }

  createForm
    .transform((data) => ({
      ...data,
      merchant_id: data.merchant_id ? Number(data.merchant_id) : null,
      client_id: data.client_id || null,
      client_name: data.client_name || null,
      client_telegram: data.client_telegram || null,
      client_contact: data.client_contact || null,
      product_name: data.product_name || null,
      product_description: data.product_description || null,
      external_invoice_id: data.external_invoice_id || null,
      callback_url: data.callback_url || null,
      tag: data.tag || null,
      metadata: metadataParsed ?? {},
    }))
    .post('/invoices', {
      preserveScroll: true,
      onSuccess: () => {
        showCreate.value = false;
        resetCreatePayload();
      },
      onError: () => {
        showCreate.value = true;
      },
      onFinish: () => {
        createForm.transform((data) => data);
      },
    });
}

watch(
  () => createForm.merchant_id,
  (value) => {
    if (value) {
      createForm.clearErrors('merchant_id');
    }
  },
);

function mapClientToOption(client: any): ClientOption {
  return {
    id: String(client.id ?? ''),
    value: String(client.external_id ?? ''),
    label: client.name || client.external_id || '',
    contact: client.contact ?? client.telegram ?? null,
    external_id: client.external_id ?? '',
  };
}

function resetClientModalForm() {
  clientModalForm.value = {
    external_id: '',
    name: '',
    telegram: '',
    contact: '',
  };
  clientModalErrors.value = {};
}

function updateClientModalForm(payload: ClientForm) {
  clientModalForm.value = { ...payload };
}

function openClientModal() {
  resetClientModalForm();
  showClientModal.value = true;
}

function closeClientModal() {
  showClientModal.value = false;
  clientModalErrors.value = {};
}

async function submitClientModal() {
  clientModalLoading.value = true;
  clientModalErrors.value = {};
  try {
    const payload = { ...clientModalForm.value };
    const response = await axios.post('/clients', payload, {
      headers: { Accept: 'application/json' },
    });
    const newClient = response.data?.client;
    if (newClient) {
      const option = mapClientToOption(newClient);
      clientOptions.value = [option, ...clientOptions.value.filter((opt) => opt.id !== option.id)];
      createForm.client_id = option.value;
    }
    showClientModal.value = false;
    resetClientModalForm();
  } catch (error: any) {
    const errors = error?.response?.data?.errors;
    const message = error?.response?.data?.message || error?.message;
    if (errors) {
      const parsed: Partial<Record<keyof ClientForm, string>> = {};
      Object.entries(errors).forEach(([key, msgs]) => {
        const first = Array.isArray(msgs) ? msgs[0] : String(msgs);
        parsed[key as keyof ClientForm] = first;
      });
      clientModalErrors.value = parsed;
    } else if (message) {
      clientModalErrors.value = { external_id: message };
    }
  } finally {
    clientModalLoading.value = false;
  }
}

function toIso(input: string | null | undefined): string {
  if (!input) return '';
  if (typeof input !== 'string') return '';
  if (input.includes('T')) return input; // уже ISO
  // Преобразуем 'YYYY-MM-DD HH:mm:ss' -> 'YYYY-MM-DDTHH:mm:ssZ'
  return `${input.replace(' ', 'T')}Z`;
}

watch(invoices, (collection: any) => {
  if (!selected.value) return;
  const fresh = collection?.data?.find((inv: Invoice) => inv.id === selected.value?.id);
  if (fresh) {
    selected.value = fresh;
  }
});

watch(
  () => page.props.clientOptions as any,
  (value) => {
    if (Array.isArray(value)) {
      clientOptions.value = [...(value as ClientOption[])];
    }
  },
);

</script>

<template>
  <AppLayout :breadcrumbs="[{ title: __('frontend.invoices_page.breadcrumb.home'), href: '/' }, { title: __('frontend.invoices_page.breadcrumb.title'), href: '/invoices' }]">
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button class="btn btn-primary btn-sm" @click="showCreate = true">
            <span class="hidden sm:block">{{ __('frontend.invoices_page.actions.create') }}</span>
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
        :title="__('frontend.invoices_page.filters.title')"
        :apply-label="__('frontend.invoices_page.filters.apply')"
        :reset-label="__('frontend.invoices_page.filters.reset')"
        :show-label="__('frontend.invoices_page.filters.show')"
        :hide-label="__('frontend.invoices_page.filters.hide')"
        :any-option-label="__('frontend.invoices_page.filters.any')"
        :loading="filters.processing"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <!-- List -->
      <div class="lg:card lg:bg-base-100 lg:shadow">
        <div class="lg:card-body">
          <h2 class="hidden lg:block card-title">{{ __('frontend.invoices_page.list_title') }}</h2>
          <h2 class="lg:hidden card-title mb-3">{{ __('frontend.invoices_page.list_title') }}</h2>

          <!-- Desktop Table View (lg and above) -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="table table-sm w-full">
              <thead>
                <tr>
                  <th>{{ __('frontend.invoices_page.table.id') }}</th>
                  <th>{{ __('frontend.invoices_page.table.address') }}</th>
<!--                  <th>{{ __('frontend.invoices_page.table.client') }}</th>-->
                  <th>{{ __('frontend.invoices_page.table.amount') }}</th>
                  <th>{{ __('frontend.invoices_page.table.currency') }}</th>
                  <th>{{ __('frontend.invoices_page.table.status') }}</th>
                  <th>{{ __('frontend.invoices_page.table.created') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in invoices.data" :key="inv.id">
                  <td class="font-mono text-xs">
                    <UidCopy :uid="inv.id" />
                  </td>
                  <td class="font-mono text-xs">
                    <AddressCopy v-if="inv.address" :address="inv.address" />
                    <span v-else class="opacity-60">#{{ inv.address_id }}</span>
                  </td>
<!--                  <td class="text-sm">
                    <div class="flex flex-col">
                      <span class="truncate">{{ inv.client?.name || inv.client_external_id || '—' }}</span>
                      <span v-if="inv.client?.external_id && inv.client?.name" class="text-xs text-base-content/60 truncate">
                        {{ inv.client?.external_id }}
                      </span>
                    </div>
                  </td>-->
                  <td>
                      <span class="text-sm">{{ inv.amount }}</span>
                  </td>
                  <td>
                    <CurrencyNetworkBadge :icon-size="22" :currency-label="inv.currency_label || inv.currency" :network-label="inv.network_label || inv.network" />
                  </td>
                  <td>
                    <span class="badge badge-sm" :class="{
                      'badge-warning': statuses.active.includes(inv.status),
                      'badge-success': inv.status === 'paid',
                      'badge-error': statuses.final.includes(inv.status) && inv.status !== 'paid',
                    }">{{ inv.status }}</span>
                  </td>
                  <td class="text-xs font-mono">
                    <DateTimeFormat short-year :value="toIso(inv.created_at)" />
                  </td>
                  <td class="flex items-center gap-2">
                    <ViewButton @click="openDetails(inv)" />
                    <ExternalLinkButton :href="`/pay/${inv.id}`" :title="__('frontend.invoices_page.table.open_payment_page')" />
                  </td>
                </tr>
                <tr v-if="!invoices.data.length">
                  <td colspan="8" class="text-center text-sm opacity-70 py-6">{{ __('frontend.invoices_page.table.empty') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card View (sm to lg) -->
          <div class="hidden sm:block lg:hidden space-y-3">
            <div v-for="inv in invoices.data" :key="inv.id" class="card bg-base-100 shadow-sm">
              <div class="card-body p-4">
                <!-- Header: UUID and Date -->
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <span class="text-xs opacity-70">UUID:</span>
                    <UidCopy :uid="inv.id" />
                  </div>
                  <div class="flex items-center gap-1.5 text-xs opacity-70">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <DateTimeFormat short-year :value="toIso(inv.created_at)" />
                  </div>
                </div>

                <!-- Main Content Row -->
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                  <!-- Amounts -->
                  <div class="flex items-center gap-2">
                    <div class="text-sm font-semibold">{{ inv.amount }}</div>
                    <div>
                      <CurrencyNetworkBadge :currency-label="inv.currency_label || inv.currency" :network-label="inv.network_label || inv.network" />
                    </div>
                  </div>

                  <!-- Middle Info - Centered -->
                  <div class="flex justify-center min-w-0">
                    <div class="font-mono text-xs opacity-70 truncate text-center">
                      <AddressCopy v-if="inv.address" :address="inv.address" />
                      <span v-else class="opacity-60">#{{ inv.address_id }}</span>
                    </div>
<!--                    <div class="text-xs text-base-content/70 truncate text-center">
                      {{ inv.client?.name || inv.client_external_id || '—' }}
                    </div>-->
                  </div>

                  <!-- Right Side: Status and Actions -->
                  <div class="flex items-center gap-2">
                    <div>
                      <span class="badge badge-sm" :class="{
                        'badge-warning': statuses.active.includes(inv.status),
                        'badge-success': inv.status === 'paid',
                        'badge-error': statuses.final.includes(inv.status) && inv.status !== 'paid',
                      }">
                        {{ inv.status }}
                      </span>
                    </div>
                    <ViewButton @click="openDetails(inv)" />
                    <ExternalLinkButton :href="`/pay/${inv.id}`" :title="__('frontend.invoices_page.table.open_payment_page')" />
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!invoices.data.length" class="text-center text-sm opacity-70 py-6">
              {{ __('frontend.invoices_page.table.empty') }}
            </div>
          </div>

          <!-- Small Mobile Card View (below sm) -->
            <div class="sm:hidden space-y-3">
                <div v-for="inv in invoices.data" :key="inv.id" class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Header: UUID and Date -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs opacity-70">UUID:</span>
                                <UidCopy :uid="inv.id" />
                            </div>
                            <div class="flex items-center gap-1.5 text-xs opacity-70">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <DateTimeFormat :value="toIso(inv.created_at)" short-year hide-seconds />
                            </div>
                        </div>

                        <!-- Main Content Row -->
                        <div class="flex justify-between items-center gap-3">
                            <!-- Amounts -->
                            <div class="flex items-center gap-2">
                                <div class="text-sm font-semibold">{{ inv.amount }}</div>
                                <div>
                                    <CurrencyNetworkBadge :currency-label="inv.currency_label || inv.currency" :network-label="inv.network_label || inv.network" />
                                </div>
                            </div>

                            <!-- Right Side: Status and Actions -->
                            <div>
                                  <span class="badge badge-sm" :class="{
                                    'badge-warning': statuses.active.includes(inv.status),
                                    'badge-success': inv.status === 'paid',
                                    'badge-error': statuses.final.includes(inv.status) && inv.status !== 'paid',
                                  }">
                                    {{ inv.status }}
                                  </span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center">
                                <div class="font-mono text-xs opacity-70 truncate text-center">
                                    <AddressCopy v-if="inv.address" :address="inv.address" />
                                    <span v-else class="opacity-60">#{{ inv.address_id }}</span>
                                </div>
<!--                                <div class="text-xs text-base-content/70 truncate text-center ml-2">
                                    {{ inv.client?.name || inv.client_external_id || '—' }}
                                </div>-->
                            </div>
                            <div class="flex items-center gap-2">
                                <ViewButton @click="openDetails(inv)" />
                                <ExternalLinkButton :href="`/pay/${inv.id}`" :title="__('frontend.invoices_page.table.open_payment_page')" />
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!invoices.data.length" class="text-center text-sm opacity-70 py-6">
                    {{ __('frontend.invoices_page.table.empty') }}
                </div>
            </div>

          <Pagination :links="invoices.links" />
        </div>
      </div>
    </div>

    <InvoiceDetailsModal
      v-model="showModal"
      :invoice="selected"
      :statuses="statuses"
      :send-loading="sendLoading"
      :send-error="sendError"
      :send-success="sendSuccess"
      @edit="openEdit"
      @send-callback="sendCallback"
      @close="closeDetails"
    />

    <InvoiceEditModal
      v-model="showEdit"
      :form="editForm"
      :status-options="allStatusOptions"
      :errors="editForm.errors"
      :loading="editForm.processing"
      @update:form="updateEditPayload"
      @submit="submitEdit"
      @close="closeEdit"
    />

    <InvoiceCreateModal
      v-model="showCreate"
      :form="createForm"
      :currency-network-options="currencyNetworkOptions"
      :merchant-options="merchantOptions"
      :client-options="clientOptions"
      :errors="createForm.errors"
      :loading="createForm.processing"
      @update:form="updateCreatePayload"
      @submit="submitCreate"
      @close="closeCreate"
      @create-client="openClientModal"
    />

    <ClientModal
      v-model="showClientModal"
      :form="clientModalForm"
      :errors="clientModalErrors"
      :loading="clientModalLoading"
      @update:form="updateClientModalForm"
      @submit="submitClientModal"
      @close="closeClientModal"
    />

  </AppLayout>
</template>


