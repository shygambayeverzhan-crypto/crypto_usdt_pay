<script setup lang="ts">
import PaymentFormLayout from '@/layouts/PaymentFormLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { vueLang } from '@erag/lang-sync-inertia';

type MerchantInfo = {
  id: number
  name: string
  description: string | null
  initials: string
  logo_url: string | null
  white_label_enabled: boolean
  back_url?: string | null
};

type Invoice = {
  id: string
  external_invoice_id: string | null
  address_id: number
  address?: string | null
  merchant?: MerchantInfo | null
  amount: number
  currency: string
  currency_label?: string
  network: string
  network_label?: string
  status: 'pending' | 'processing' | 'paid' | 'expired' | 'cancelled' | string
  txid: string | null
  tx_explorer_url?: string | null
  amount_received: number
  confirmations: number
  expires_at: string | null
  callback_url: string | null
  tag: string | null
  metadata: Record<string, any> | null
  product_name?: string | null
  product_description?: string | null
  created_at: string | null
  updated_at: string | null
}

type LocaleOption = {
  code: string;
  label: string;
  flag: string;
};

const page = usePage();
const appName = computed(
  () => (page.props.appName as string) || ((import.meta as any).env?.VITE_APP_NAME as string) || 'App'
);
const initial = page.props.invoice as Invoice;
const statuses = computed(() => page.props.statuses as { active: string[]; final: string[] });
const { __ } = vueLang();

const invoice = ref<Invoice>(initial);
const localeMenuOpen = ref(false);

const fallbackLocales: LocaleOption[] = [
  { code: 'ru', label: 'Русский', flag: 'RU' },
  { code: 'en', label: 'English', flag: 'US' },
];

const sharedLocales = computed(() => (page.props as any)?.locales as { available?: LocaleOption[]; enabled?: string[] } | undefined);

const availableLocales = computed<LocaleOption[]>(() => {
  const available = sharedLocales.value?.available ?? [];
  if (available.length === 0) {
    return fallbackLocales;
  }

  const enabled = sharedLocales.value?.enabled ?? [];
  const enabledSet = enabled.length > 0 ? new Set(enabled) : null;
  const filtered = enabledSet ? available.filter((item) => enabledSet.has(item.code)) : available;

  return filtered.length > 0 ? filtered : available;
});

const currentLocale = computed(() => ((page.props as any)?.locale as string) || availableLocales.value[0]?.code || 'ru');
const currentLocaleOption = computed<LocaleOption>(() => availableLocales.value.find((l) => l.code === currentLocale.value) ?? availableLocales.value[0] ?? fallbackLocales[0]);

function switchLocale(code: string) {
  if (code === currentLocale.value) {
    localeMenuOpen.value = false;
    return;
  }

  const allowedCodes = new Set(availableLocales.value.map((locale) => locale.code));
  if (!allowedCodes.has(code)) return;

  localeMenuOpen.value = false;
  router.get(`/lang/${code}`, {}, { preserveScroll: true, preserveState: false });
}

const qrUrl = computed(() => `/pay/${invoice.value.id}/qr`);

let timer: number | null = null;

async function refresh() {
  try {
    const { data } = await axios.get(`/pay/${invoice.value.id}/data`);
    invoice.value = data as Invoice;
    if (statuses.value.final?.includes(invoice.value.status)) {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }
  } catch {}
}

onMounted(() => {
  timer = window.setInterval(refresh, 10000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});

const statusText = computed(() => {
  const s = invoice.value.status;
  if (s === 'paid') return __('frontend.payment_form.status.paid');
  if (s === 'processing') return __('frontend.payment_form.status.processing');
  if (s === 'expired') return __('frontend.payment_form.status.expired');
  if (s === 'cancelled') return __('frontend.payment_form.status.cancelled');
  return __('frontend.payment_form.status.pending');
});

type WhiteLabelInfo = {
  storeName: string;
  storeDescription: string;
  productName: string;
  productDescription: string;
  initials: string;
  logoUrl: string | null;
  backUrl: string | null;
  backLabel: string;
};

const hasMerchantWhiteLabel = computed(() => {
  const merchant = invoice.value.merchant;
  return Boolean(merchant && merchant.white_label_enabled);
});

const whiteLabelInfo = computed<WhiteLabelInfo | null>(() => {
  const merchant = invoice.value.merchant;
  if (!merchant || !merchant.white_label_enabled) {
    return null;
  }

  const productName = invoice.value.product_name || null;
  const productDescription = invoice.value.product_description || null;
  const backUrl = merchant.back_url || null;

  return {
    storeName: merchant.name,
    storeDescription: merchant.description ?? '',
    productName: productName ?? '',
    productDescription: productDescription ?? '',
    initials: merchant.initials,
    logoUrl: merchant.logo_url,
    backUrl,
    backLabel: __('frontend.payment_form.white_label.back_label'),
  };
});

const invoiceIdShort = computed(() => {
  const src = (invoice.value.id ?? '').trim();
  if (src.length <= 8) return src;
  return src.slice(-8);
});

const currencyNetworkText = computed(() => __('frontend.payment_form.qr.currency_network', {
  currency: invoice.value.currency_label || invoice.value.currency,
  network: invoice.value.network_label || invoice.value.network,
}));

const statusBadgeClass = computed(() => {
  const s = invoice.value.status;
  if (s === 'paid') return 'border-emerald-400/30 bg-emerald-400/10 text-emerald-200';
  if (s === 'cancelled' || s === 'expired') return 'border-rose-400/30 bg-rose-400/10 text-rose-200';
  if (s === 'processing') return 'border-sky-400/30 bg-sky-400/10 text-sky-200';
  return 'border-amber-400/30 bg-amber-400/10 text-amber-200';
});

const invoiceTooltipText = ref(__('frontend.common.copy'));
const amountTooltipText = ref(__('frontend.payment_form.amount.copy'));
const addressTooltipText = ref(__('frontend.payment_form.address.copy'));
let invoiceTooltipTimer: number | undefined;
let amountTooltipTimer: number | undefined;
let addressTooltipTimer: number | undefined;

async function writeClipboardWithFallback(value: string): Promise<boolean> {
  const text = String(value ?? '');

  try {
    if (window.isSecureContext && navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return true;
    }
  } catch {}

  try {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '-1000px';
    textarea.style.left = '-1000px';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);
    const ok = document.execCommand('copy');
    document.body.removeChild(textarea);
    return ok;
  } catch {
    return false;
  }
}

async function copyText(value: string, tooltip: typeof invoiceTooltipText, copiedText: string, failedText: string, resetText: string, timerSetter: (timer: number) => void) {
  const ok = await writeClipboardWithFallback(value);
  tooltip.value = ok ? copiedText : failedText;
  timerSetter(window.setTimeout(() => {
    tooltip.value = resetText;
  }, 1500));
}

function copyInvoiceId() {
  if (invoiceTooltipTimer) clearTimeout(invoiceTooltipTimer);
  copyText(
    invoice.value.id,
    invoiceTooltipText,
    __('frontend.common.copied'),
    __('frontend.common.copy_failed'),
    __('frontend.common.copy'),
    (timer) => {
      invoiceTooltipTimer = timer;
    },
  );
}

function copyAmount() {
  if (amountTooltipTimer) clearTimeout(amountTooltipTimer);
  copyText(
    String(invoice.value.amount ?? ''),
    amountTooltipText,
    __('frontend.payment_form.amount.copied'),
    __('frontend.payment_form.amount.copy_failed'),
    __('frontend.payment_form.amount.copy'),
    (timer) => {
      amountTooltipTimer = timer;
    },
  );
}

function copyAddress() {
  if (!invoice.value.address) return;
  if (addressTooltipTimer) clearTimeout(addressTooltipTimer);
  copyText(
    invoice.value.address,
    addressTooltipText,
    __('frontend.payment_form.address.copied'),
    __('frontend.payment_form.address.copy_failed'),
    __('frontend.payment_form.address.copy'),
    (timer) => {
      addressTooltipTimer = timer;
    },
  );
}

function onCopyKeydown(e: KeyboardEvent, callback: () => void) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    callback();
  }
}

function parseIsoToMs(s: string | null): number | null {
  if (!s) return null;
  const ms = Date.parse(s);
  return Number.isFinite(ms) ? ms : null;
}

const expiresAtMs = computed(() => parseIsoToMs(invoice.value.expires_at));
const remainingSeconds = ref(0);
const countdownMinutes = computed(() => Math.floor(remainingSeconds.value / 60));
const countdownSeconds = computed(() => remainingSeconds.value % 60);
let countdownTimer: number | null = null;

function tickCountdown() {
  if (!expiresAtMs.value) {
    remainingSeconds.value = 0;
    return;
  }

  const diff = Math.max(0, Math.floor((expiresAtMs.value - Date.now()) / 1000));
  remainingSeconds.value = diff;
  if (diff === 0 && countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
}

function startCountdown() {
  tickCountdown();
  if (countdownTimer) clearInterval(countdownTimer);
  if (expiresAtMs.value) {
    countdownTimer = window.setInterval(tickCountdown, 1000);
  }
}

watch(() => invoice.value.expires_at, startCountdown);

onMounted(() => {
  startCountdown();
});

onBeforeUnmount(() => {
  if (countdownTimer) clearInterval(countdownTimer);
  if (invoiceTooltipTimer) clearTimeout(invoiceTooltipTimer);
  if (amountTooltipTimer) clearTimeout(amountTooltipTimer);
  if (addressTooltipTimer) clearTimeout(addressTooltipTimer);
});

</script>

<template>
  <PaymentFormLayout>
    <div class="relative isolate min-h-screen overflow-hidden bg-[#0c0c0c] text-zinc-100">
      <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)] [background-size:36px_36px] [mask-image:radial-gradient(ellipse_90%_75%_at_50%_28%,#000_0%,transparent_72%)]"
      ></div>

      <div class="mx-auto flex w-full justify-end px-4 pt-5 sm:px-6 sm:pt-6 xl:px-0" :class="hasMerchantWhiteLabel ? 'max-w-7xl' : 'max-w-[32.5rem]'">
        <div class="relative">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1.5 text-sm font-semibold text-slate-100 shadow-md shadow-black/20 backdrop-blur transition hover:border-amber-300/40 hover:bg-amber-300/10 focus:outline-none focus:ring-2 focus:ring-amber-300/70"
            :aria-expanded="localeMenuOpen"
            @click="localeMenuOpen = !localeMenuOpen"
          >
            <span class="text-xs uppercase tracking-[0.18em] text-amber-100">{{ currentLocaleOption.code }}</span>
            <span>{{ currentLocaleOption.label }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-slate-300">
              <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
          </button>
          <div
            v-if="localeMenuOpen"
            class="absolute right-0 z-20 mt-1.5 w-48 overflow-hidden rounded-xl border border-white/10 bg-slate-950/95 p-1.5 shadow-xl shadow-black/40 backdrop-blur"
          >
            <button
              v-for="locale in availableLocales"
              :key="locale.code"
              type="button"
              class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-sm text-slate-200 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-amber-300/70"
              :class="{ 'bg-amber-300/10 text-amber-100': locale.code === currentLocale }"
              @click="switchLocale(locale.code)"
            >
              <span>{{ locale.label }}</span>
              <span class="text-xs uppercase tracking-[0.16em] text-slate-400">{{ locale.code }}</span>
            </button>
          </div>
        </div>
      </div>

      <main class="mx-auto w-full px-4 pb-8 pt-4 sm:px-6 sm:pt-5 xl:px-0" :class="hasMerchantWhiteLabel ? 'max-w-7xl' : 'max-w-[32.5rem]'">
        <header class="mb-4 rounded-2xl border border-white/10 bg-white/[0.04] p-3 shadow-xl shadow-black/25 backdrop-blur sm:p-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="shrink-0">
              <p class="mb-1 text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">
                {{ appName }}
              </p>
              <h1 class="flex flex-nowrap items-center gap-2 text-lg font-semibold tracking-tight text-white sm:text-xl">
                <span class="whitespace-nowrap">{{ __('frontend.payment_form.page_title', { id: '' }) }}</span>
                <button
                  v-if="invoice?.id"
                  type="button"
                  class="group relative shrink-0 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 font-mono text-sm text-amber-100 transition hover:border-amber-300/40 hover:bg-amber-300/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                  :title="invoice.id"
                  @click="copyInvoiceId"
                  @keydown="onCopyKeydown($event, copyInvoiceId)"
                >
                  {{ invoiceIdShort }}
                  <span class="pointer-events-none absolute -top-10 left-1/2 z-[100] -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs text-slate-100 opacity-0 shadow-lg ring-1 ring-white/10 transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">
                    {{ invoiceTooltipText }}
                  </span>
                </button>
              </h1>
            </div>
            <div class="flex min-w-0 flex-1 self-start sm:justify-end sm:self-center">
              <div
                class="flex max-w-full min-w-0 items-center rounded-full border px-2.5 py-1 text-sm font-medium shadow-md shadow-black/20"
                :class="statusBadgeClass"
              >
                <span class="mr-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-current"></span>
                <span class="min-w-0 truncate text-left" :title="statusText">{{ statusText }}</span>
              </div>
            </div>
          </div>
        </header>

        <div class="grid items-start gap-4" :class="hasMerchantWhiteLabel ? 'lg:grid-cols-[0.9fr_1.1fr]' : 'lg:grid-cols-1'">
          <aside v-if="whiteLabelInfo" class="h-full overflow-hidden rounded-2xl border border-white/10 bg-white/[0.05] shadow-xl shadow-black/25 backdrop-blur">
            <div class="flex h-full flex-col p-3 sm:p-4">
              <div class="space-y-4">
                <div class="flex items-center gap-3">
                  <div class="relative shrink-0">
                    <div class="h-12 w-12 overflow-hidden rounded-xl border border-amber-300/20 bg-gradient-to-br from-amber-300 to-violet-500 p-0.5 shadow-md shadow-amber-500/10">
                      <img
                        v-if="whiteLabelInfo.logoUrl"
                        :src="whiteLabelInfo.logoUrl"
                        alt="logo"
                        class="h-full w-full rounded-[0.65rem] object-cover"
                      />
                      <span v-else class="flex h-full w-full items-center justify-center rounded-[0.65rem] bg-slate-950 text-base font-semibold text-white">
                        {{ whiteLabelInfo.initials }}
                      </span>
                    </div>
                  </div>
                  <div class="min-w-0 space-y-1">
                    <h2 class="text-lg font-semibold leading-snug text-white">{{ whiteLabelInfo.storeName }}</h2>
                    <p class="text-sm leading-5 text-slate-300">
                      {{ whiteLabelInfo.storeDescription || __('frontend.payment_form.white_label.store_description') }}
                    </p>
                  </div>
                </div>

                <div v-if="whiteLabelInfo.productName" class="rounded-xl border border-white/10 bg-slate-950/45 p-3">
                  <p class="mb-1 text-xs font-medium uppercase tracking-[0.16em] text-slate-400">{{ __('frontend.payment_form.white_label.product_title') }}</p>
                  <p class="text-sm font-semibold text-slate-100 sm:text-base">
                    {{ whiteLabelInfo.productName }}
                    <span v-if="whiteLabelInfo.productDescription" class="block pt-0.5 text-sm font-normal leading-5 text-slate-400">
                      {{ whiteLabelInfo.productDescription }}
                    </span>
                  </p>
                </div>
              </div>

              <div v-if="whiteLabelInfo.backUrl" class="mt-auto pt-4">
                <a
                  :href="whiteLabelInfo.backUrl"
                  class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm font-medium text-slate-200 transition hover:border-amber-300/40 hover:bg-amber-300/10 hover:text-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-300/70"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path d="M10.828 12 16 17.172 14.586 18.586 7 11l7.586-7.586L16 4.828 10.828 10H20v2h-9.172Z" />
                  </svg>
                  <span>{{ whiteLabelInfo.backLabel }}</span>
                </a>
              </div>
            </div>
          </aside>

          <div class="flex w-full flex-col gap-3" :class="hasMerchantWhiteLabel ? 'lg:mx-auto lg:max-w-md xl:max-w-lg' : ''">
            <section v-if="invoice.status === 'paid'" class="rounded-2xl border border-emerald-300/20 bg-emerald-400/[0.06] p-5 text-center shadow-xl shadow-black/25 backdrop-blur sm:p-6">
              <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full border border-emerald-300/25 bg-emerald-300/10 text-emerald-200 shadow-md shadow-emerald-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
              </div>
              <h2 class="text-xl font-semibold text-white">{{ __('frontend.payment_form.cards.paid_title') }}</h2>
              <a
                v-if="invoice.tx_explorer_url"
                :href="invoice.tx_explorer_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-3 inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-sm font-semibold text-emerald-100 transition hover:bg-emerald-300/20 focus:outline-none focus:ring-2 focus:ring-emerald-300/70"
              >
                <span>{{ __('frontend.payment_form.cards.open_explorer') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
              </a>
            </section>

            <section v-else-if="invoice.status === 'expired' || invoice.status === 'cancelled'" class="rounded-2xl border border-rose-300/20 bg-rose-400/[0.06] p-5 text-center shadow-xl shadow-black/25 backdrop-blur sm:p-6">
              <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full border border-rose-300/25 bg-rose-300/10 text-rose-200 shadow-md shadow-rose-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
              </div>
              <h2 class="text-xl font-semibold text-white">
                {{ invoice.status === 'cancelled' ? __('frontend.payment_form.cards.cancelled_title') : __('frontend.payment_form.cards.expired_title') }}
              </h2>
            </section>

            <section v-else class="overflow-visible rounded-2xl border border-white/10 bg-white/[0.06] shadow-xl shadow-black/25 backdrop-blur">
              <div class="border-b border-white/10 bg-slate-950/30 p-3 sm:p-3.5">
                <div class="flex items-start gap-2 rounded-xl border border-amber-300/20 bg-amber-300/10 p-2.5 text-amber-100">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mt-0.5 h-4 w-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                  </svg>
                  <span class="text-sm leading-5">{{ __('frontend.payment_form.important') }}</span>
                </div>
              </div>

              <div class="grid gap-1.5 px-4 pt-4 pb-3 sm:gap-2 sm:px-5 sm:pb-4">
                <div class="text-center pb-3 sm:pb-4">
                  <p class="mb-3 text-xs font-medium uppercase tracking-[0.18em] text-slate-400 sm:mb-4">{{ currencyNetworkText }}</p>
                  <div class="mx-auto flex aspect-square w-36 max-w-full items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-white p-2 shadow-lg shadow-black/20 sm:w-44">
                    <img
                      v-if="invoice.address"
                      :src="qrUrl"
                      :alt="__('frontend.payment_form.qr.alt')"
                      class="h-full w-full object-contain"
                      loading="eager"
                      decoding="async"
                    />
                  </div>
                </div>

                <div class="grid min-w-0 gap-1.5">
                  <div
                    class="grid min-w-0 grid-cols-1 gap-1.5"
                    :class="invoice.expires_at ? 'sm:grid-cols-2 sm:items-start' : ''"
                  >
                    <div class="flex min-h-0 min-w-0 flex-col gap-1 rounded-lg border border-white/10 bg-slate-950/30 p-3">
                      <div class="text-xs font-medium leading-none text-slate-400">{{ __('frontend.payment_form.amount.title') }}</div>
                      <div class="flex min-w-0 flex-row items-baseline justify-between gap-2 pl-1">
                        <button
                          type="button"
                          class="group relative inline-flex min-w-0 flex-1 items-center justify-start gap-1 rounded-md py-0.5 font-mono text-base font-semibold leading-none tabular-nums text-white transition hover:text-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:text-lg"
                          @click="copyAmount"
                          @keydown="onCopyKeydown($event, copyAmount)"
                        >
                          <span class="min-w-0 truncate text-left">{{ String(invoice.amount) }}</span>
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 shrink-0 text-amber-200/70 transition group-hover:text-amber-200">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                          </svg>
                          <span class="pointer-events-none absolute -top-9 left-1/2 z-[100] -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-slate-100 opacity-0 shadow-lg ring-1 ring-white/10 transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">
                            {{ amountTooltipText }}
                          </span>
                        </button>
                        <span class="inline-flex w-fit shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-violet-300/25 bg-violet-300/10 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-violet-100 sm:text-xs sm:tracking-[0.1em]">
                          {{ invoice.currency_label || invoice.currency }}
                          <span class="h-1 w-1 rounded-full bg-violet-200"></span>
                          {{ invoice.network_label || invoice.network }}
                        </span>
                      </div>
                    </div>

                    <div
                      v-if="invoice.expires_at"
                      class="flex min-h-0 min-w-0 flex-col gap-1 rounded-lg border border-white/10 bg-slate-950/30 p-3"
                    >
                      <div class="text-xs font-medium leading-none text-slate-400">{{ __('frontend.payment_form.countdown.title') }}</div>
                      <div class="w-full min-w-0 pl-1">
                        <div
                          class="inline-flex w-full items-baseline gap-0 font-mono text-base font-semibold tabular-nums leading-none text-white sm:text-lg"
                        >
                          <span :aria-label="String(countdownMinutes)" aria-live="polite">{{ String(countdownMinutes).padStart(2, '0') }}</span>
                          <span class="mx-px shrink-0 select-none" aria-hidden="true">:</span>
                          <span :aria-label="String(countdownSeconds)" aria-live="polite">{{ String(countdownSeconds).padStart(2, '0') }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="invoice.address" class="flex min-w-0 flex-col gap-1 rounded-lg border border-white/10 bg-slate-950/30 p-3">
                    <div class="text-xs font-medium leading-none text-slate-400">{{ __('frontend.payment_form.address.title') }}</div>
                    <button
                      type="button"
                      class="group relative w-full rounded-md py-0.5 pl-1 text-left font-mono text-xs leading-tight text-slate-100 transition hover:text-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:text-sm sm:leading-snug"
                      @click="copyAddress"
                      @keydown="onCopyKeydown($event, copyAddress)"
                    >
                      <span class="break-all align-middle [overflow-wrap:anywhere]">{{ invoice.address }}</span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="ml-1 inline-block h-4 w-4 shrink-0 align-middle text-amber-200/70 transition group-hover:text-amber-200">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                      </svg>
                      <span class="pointer-events-none absolute -top-9 left-1/2 z-[100] -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-slate-100 opacity-0 shadow-lg ring-1 ring-white/10 transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">
                        {{ addressTooltipText }}
                      </span>
                    </button>
                  </div>

                  <div v-if="invoice.txid" class="flex flex-col gap-1 rounded-lg border border-white/10 bg-slate-950/30 p-3">
                    <div class="text-xs font-medium leading-none text-slate-400">{{ __('frontend.payment_form.transaction.title') }}</div>
                    <a
                      v-if="invoice.tx_explorer_url"
                      :href="invoice.tx_explorer_url || '#'"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-1.5 pl-1 text-xs font-semibold text-amber-200 transition hover:text-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:text-sm"
                    >
                      {{ __('frontend.payment_form.transaction.explorer') }}
                    </a>
                  </div>
                </div>

                <ul class="list-none space-y-1 border-t border-white/10 pt-3 text-xs leading-5 text-slate-400">
                  <li class="flex gap-2.5">
                    <span class="mt-0.5 shrink-0 select-none leading-none text-slate-500" aria-hidden="true">–</span>
                    <span class="min-w-0">{{ __('frontend.payment_form.rules.exact_amount') }}</span>
                  </li>
                  <li class="flex gap-2.5">
                    <span class="mt-0.5 shrink-0 select-none leading-none text-slate-500" aria-hidden="true">–</span>
                    <span class="min-w-0">{{ __('frontend.payment_form.rules.network_match') }}</span>
                  </li>
                  <li class="flex gap-2.5">
                    <span class="mt-0.5 shrink-0 select-none leading-none text-slate-500" aria-hidden="true">–</span>
                    <span class="min-w-0">{{ __('frontend.payment_form.rules.auto_refresh') }}</span>
                  </li>
                </ul>
              </div>
            </section>
          </div>
        </div>
      </main>
    </div>
  </PaymentFormLayout>
</template>
