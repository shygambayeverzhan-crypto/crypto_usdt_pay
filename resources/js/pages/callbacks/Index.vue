<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue';
import CallbackDetailsModal from '@/components/modals/callbacks/CallbackDetailsModal.vue';
import UidCopy from '@/components/ui/UidCopy.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { vueLang } from '@erag/lang-sync-inertia';
import FilterPanel from '@/components/filters/FilterPanel.vue';

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

type StatusGroup = '' | 'success' | 'error';

interface CallbackFilters {
  search: string;
  status_group: StatusGroup;
  event: string;
  invoice_id: string;
  has_error: boolean;
}

type FilterField = {
  key: keyof CallbackFilters;
  type: 'text' | 'select' | 'checkpoint';
  label: string;
  placeholder?: string;
  options?: { value: string; label: string }[];
};

const page = usePage();
const logs = computed(() => page.props.logs as any);
const pageFilters = computed(() => (page.props.filters as Partial<CallbackFilters> | undefined) ?? {});
const { __ } = vueLang();

const selected = ref<CallbackLog | null>(null);
const showModal = ref(false);

const filterDefaults: CallbackFilters = {
  search: '',
  status_group: '',
  event: '',
  invoice_id: '',
  has_error: false,
};

const filters = useForm<CallbackFilters>({
  ...filterDefaults,
  ...pageFilters.value,
});

const filtersModel = computed({
  get: () => ({
    ...filterDefaults,
    ...filters.data(),
  }),
  set: (value: CallbackFilters) => {
    filters.search = value.search ?? '';
    filters.status_group = (value.status_group ?? '') as StatusGroup;
    filters.event = value.event ?? '';
    filters.invoice_id = value.invoice_id ?? '';
    filters.has_error = Boolean(value.has_error);
  },
});

const statusOptions = computed(() => [
  { value: 'success', label: __('frontend.callbacks.filters.status_success') },
  { value: 'error', label: __('frontend.callbacks.filters.status_error') },
]);

const filterFields = computed<FilterField[]>(() => [
  {
    key: 'search',
    type: 'text',
    label: __('frontend.callbacks.filters.search'),
    placeholder: __('frontend.callbacks.filters.search_placeholder'),
  },
  {
    key: 'invoice_id',
    type: 'text',
    label: __('frontend.callbacks.filters.invoice_id'),
    placeholder: __('frontend.callbacks.filters.invoice_id_placeholder'),
  },
  {
    key: 'event',
    type: 'text',
    label: __('frontend.callbacks.filters.event'),
    placeholder: __('frontend.callbacks.filters.event_placeholder'),
  },
  {
    key: 'status_group',
    type: 'select',
    label: __('frontend.callbacks.filters.status_group'),
    options: statusOptions.value,
  },
  {
    key: 'has_error',
    type: 'checkpoint',
    label: __('frontend.callbacks.filters.has_error'),
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
    .get('/callback-logs', {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      onFinish: () => filters.transform(data => data),
    });
}

function resetFilters() {
  filters.search = filterDefaults.search;
  filters.status_group = filterDefaults.status_group;
  filters.event = filterDefaults.event;
  filters.invoice_id = filterDefaults.invoice_id;
  filters.has_error = filterDefaults.has_error;

  applyFilters();
}

function openDetails(log: CallbackLog) {
  selected.value = log;
  showModal.value = true;
}

function closeDetails() {
  showModal.value = false;
}

function toIso(input: string | null | undefined): string {
  if (!input) return '';
  if (typeof input !== 'string') return '';
  if (input.includes('T')) return input;
  return `${input.replace(' ', 'T')}Z`;
}

function formatDuration(ms: number | null | undefined): string {
  if (ms === null || ms === undefined) return '—';
  const seconds = ms / 1000;
  return seconds.toFixed(3);
}
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: __('frontend.callbacks.breadcrumb.home'), href: '/' }, { title: __('frontend.callbacks.breadcrumb.title'), href: '/callback-logs' }]">
    <div class="grid gap-6">
      <FilterPanel
        v-model="filtersModel"
        :fields="filterFields"
        :title="__('frontend.callbacks.filters.title')"
        :apply-label="__('frontend.callbacks.filters.apply')"
        :reset-label="__('frontend.callbacks.filters.reset')"
        :show-label="__('frontend.callbacks.filters.show')"
        :hide-label="__('frontend.callbacks.filters.hide')"
        :any-option-label="__('frontend.callbacks.filters.any')"
        :loading="filters.processing"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <!-- List -->
      <div class="lg:card lg:bg-base-100 lg:shadow">
        <div class="lg:card-body">
          <h2 class="hidden lg:block card-title">{{ __('frontend.callbacks.list_title') }}</h2>
          <h2 class="lg:hidden card-title mb-3">{{ __('frontend.callbacks.list_title') }}</h2>

          <!-- Desktop Table View (lg and above) -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="table table-sm w-full">
              <thead>
                <tr>
                  <th>{{ __('frontend.callbacks.table.id') }}</th>
                  <th>{{ __('frontend.callbacks.table.invoice') }}</th>
                  <th>{{ __('frontend.callbacks.table.event') }}</th>
                  <th>{{ __('frontend.callbacks.table.http') }}</th>
                  <th>{{ __('frontend.callbacks.table.time') }}</th>
                  <th>{{ __('frontend.callbacks.table.duration') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs.data" :key="log.id">
                  <td class="font-mono text-xs">
                    <UidCopy :uid="log.id"/>
                  </td>
                  <td class="font-mono text-xs">
                    <UidCopy :uid="log.invoice_id"/>
                  </td>
                  <td>
                    <span class="badge badge-sm badge-outline">{{ log.event }}</span>
                  </td>
                  <td>
                    <span class="badge badge-sm" :class="{ 'badge-success': (log.response_status||0) >= 200 && (log.response_status||0) < 300, 'badge-error': (log.response_status||0) >= 400 }">
                      {{ log.response_status ?? '—' }}
                    </span>
                  </td>
                  <td class="text-xs font-mono">
                    <DateTimeFormat short-year :value="toIso(log.created_at)" />
                  </td>
                  <td>{{ formatDuration(log.duration_ms) }}</td>
                  <td>
                    <button class="btn btn-xs" @click="openDetails(log)">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                      </svg>
                    </button>
                  </td>
                </tr>
                <tr v-if="!logs.data.length">
                  <td colspan="7" class="text-center text-sm opacity-70 py-6">{{ __('frontend.callbacks.table.empty') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card View (sm to lg) -->
          <div class="hidden sm:block lg:hidden space-y-3">
            <div v-for="log in logs.data" :key="log.id" class="card bg-base-100 shadow-sm">
              <div class="card-body p-4">
                <!-- Header: UUID and Date -->
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                        <span class="text-xs opacity-70">UUID:</span>
                    <UidCopy :uid="log.id" />
                  </div>
                  <div class="flex items-center gap-1.5 text-xs opacity-70">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <DateTimeFormat short-year :value="toIso(log.created_at)" />
                  </div>
                </div>

                <!-- Main Content Row -->
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                  <!-- Event and Status -->
                    <div class="flex  items-center gap-2">
                        <span class="text-xs opacity-70">{{ __('frontend.callbacks.table.invoice') }}:</span>
                        <div class="text-center">
                            <UidCopy :uid="log.invoice_id" />
                        </div>
                    </div>

                    <!-- Middle Info -->
                  <div class="flex items-center justify-center min-w-0 gap-2">
                    <span class="badge badge-sm badge-outline">{{ log.event }}</span>
                    <span class="badge badge-sm" :class="{ 'badge-success': (log.response_status||0) >= 200 && (log.response_status||0) < 300, 'badge-error': (log.response_status||0) >= 400 }">
                      {{ log.response_status ?? __('frontend.login_history.table.ip') }}
                    </span>
                  </div>


                  <!-- Right Side: Duration and Actions -->
                  <div class="flex items-center gap-3">
                    <div class="text-xs opacity-70">
                      {{ formatDuration(log.duration_ms) }} {{ __('frontend.callbacks.table.duration_suffix') }}
                    </div>
                    <button class="btn btn-xs" @click="openDetails(log)">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!logs.data.length" class="text-center text-sm opacity-70 py-6">
              {{ __('frontend.callbacks.table.empty') }}
            </div>
          </div>

          <!-- Small Mobile Card View (below sm) -->
          <div class="sm:hidden space-y-3">
            <div v-for="log in logs.data" :key="log.id" class="card bg-base-100 shadow-sm">
              <div class="card-body p-4">
                <!-- Header: UUID and Date -->
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <span class="text-xs opacity-70">UUID:</span>
                    <UidCopy :uid="log.id" />
                  </div>
                  <div class="flex items-center gap-1.5 text-xs opacity-70">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <DateTimeFormat :value="toIso(log.created_at)" short-year hide-seconds />
                  </div>
                </div>

                <!-- Main Content Row -->
                <div class="flex justify-between items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs opacity-70">{{ __('frontend.callbacks.table.invoice') }}:</span>
                        <div class="text-center">
                            <UidCopy :uid="log.invoice_id" />
                        </div>
                    </div>

                  <!-- Right Side: Duration -->
                  <div class="text-xs opacity-70">
                    {{ formatDuration(log.duration_ms) }} {{ __('frontend.callbacks.table.duration_suffix') }}
                  </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between mt-3">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-sm badge-outline">{{ log.event }}</span>
                        <span class="badge badge-sm" :class="{ 'badge-success': (log.response_status||0) >= 200 && (log.response_status||0) < 300, 'badge-error': (log.response_status||0) >= 400 }">
                      {{ log.response_status ?? '—' }}
                    </span>
                    </div>
                  <div class="flex items-center gap-2">
                    <button class="btn btn-xs" @click="openDetails(log)">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!logs.data.length" class="text-center text-sm opacity-70 py-6">
              {{ __('frontend.callbacks.table.empty') }}
            </div>
          </div>

          <Pagination :links="logs.links" />
        </div>
      </div>
    </div>

    <CallbackDetailsModal
      v-model="showModal"
      :log="selected"
      @close="closeDetails"
    />

  </AppLayout>

</template>


