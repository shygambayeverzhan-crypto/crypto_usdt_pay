<script setup lang="ts">
import FlagIcon from '@/components/ui/FlagIcon.vue';
import { dashboard, login, register } from '@/routes';
import { vueLang } from '@erag/lang-sync-inertia';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

withDefaults(defineProps<{ canRegister: boolean }>(), { canRegister: true });

type LocaleOption = {
    code: string;
    label: string;
    flag: string;
};

const page = usePage();
const { __ } = vueLang();

const appName = computed(() => (page.props as any)?.name ?? 'Payment System');
const localeMenuOpen = ref(false);

const fallbackLocales: LocaleOption[] = [
    { code: 'ru', label: 'Русский', flag: 'RU' },
    { code: 'en', label: 'English', flag: 'US' },
];

const sharedLocales = computed(
    () =>
        (page.props as any)?.locales as
            | { available?: LocaleOption[]; enabled?: string[] }
            | undefined,
);

const availableLocales = computed<LocaleOption[]>(() => {
    const available = sharedLocales.value?.available ?? [];

    if (available.length === 0) {
        return fallbackLocales;
    }

    const enabled = sharedLocales.value?.enabled ?? [];
    const enabledSet = enabled.length > 0 ? new Set(enabled) : null;
    const filtered = enabledSet
        ? available.filter((item) => enabledSet.has(item.code))
        : available;

    return filtered.length > 0 ? filtered : available;
});

const currentLocale = computed(
    () =>
        ((page.props as any)?.locale as string) ||
        availableLocales.value[0]?.code ||
        'ru',
);
const currentLocaleOption = computed<LocaleOption>(
    () =>
        availableLocales.value.find(
            (locale) => locale.code === currentLocale.value,
        ) ??
        availableLocales.value[0] ??
        fallbackLocales[0],
);

const stats = computed(() => [
    { value: '99.95%', label: __('frontend.welcome.stat_uptime') },
    { value: '<10s', label: __('frontend.welcome.stat_callback') },
    { value: '24/7', label: __('frontend.welcome.stat_monitoring') },
]);

const features = computed(() => [
    {
        title: __('frontend.welcome.feature_dashboard_title'),
        description: __('frontend.welcome.feature_dashboard_description'),
        accent: 'from-cyan-300 to-blue-500',
    },
    {
        title: __('frontend.welcome.feature_callbacks_title'),
        description: __('frontend.welcome.feature_callbacks_description'),
        accent: 'from-violet-300 to-fuchsia-500',
    },
    {
        title: __('frontend.welcome.feature_security_title'),
        description: __('frontend.welcome.feature_security_description'),
        accent: 'from-amber-200 to-orange-500',
    },
]);

const networks = ['BTC', 'ETH', 'USDT', 'TRON', 'BSC'];

function switchLocale(code: string) {
    if (code === currentLocale.value) {
        localeMenuOpen.value = false;

        return;
    }

    const allowedCodes = new Set(
        availableLocales.value.map((locale) => locale.code),
    );

    if (!allowedCodes.has(code)) {
        return;
    }

    localeMenuOpen.value = false;
    router.get(
        `/lang/${code}`,
        {},
        { preserveScroll: true, preserveState: false },
    );
}

onMounted(() => {
    document.documentElement.classList.add('page-welcome');
});

onBeforeUnmount(() => {
    document.documentElement.classList.remove('page-welcome');
});
</script>

<template>
    <Head :title="appName" />

    <main
        class="relative isolate min-h-screen overflow-x-hidden bg-[#030712] text-slate-100"
    >
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(34,211,238,0.18),transparent_28%),radial-gradient(circle_at_78%_8%,rgba(139,92,246,0.22),transparent_30%),radial-gradient(circle_at_55%_78%,rgba(245,158,11,0.12),transparent_34%)]"
            ></div>
            <div
                class="absolute inset-0 bg-[linear-gradient(rgba(148,163,184,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.04)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_center,black,transparent_78%)] bg-[size:72px_72px]"
            ></div>
            <div
                class="absolute top-0 left-1/2 h-[42rem] w-[42rem] -translate-x-1/2 rounded-full border border-cyan-300/10 bg-cyan-300/5 blur-3xl"
            ></div>
            <div
                class="absolute top-28 right-0 h-[min(24rem,50vw)] max-h-[24rem] w-[min(24rem,50vw)] max-w-[24rem] translate-x-1/4 rounded-full bg-violet-600/20 blur-3xl"
            ></div>
            <div
                class="absolute bottom-0 left-0 h-[min(22rem,45vw)] max-h-[22rem] w-[min(22rem,45vw)] max-w-[22rem] -translate-x-1/4 translate-y-1/4 rounded-full bg-amber-500/10 blur-3xl"
            ></div>
        </div>

        <header
            class="relative z-20 mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10"
        >
            <Link
                href="/"
                class="group flex items-center gap-3 rounded-full outline-none focus-visible:ring-2 focus-visible:ring-cyan-300/80 focus-visible:ring-offset-2 focus-visible:ring-offset-[#030712]"
            >
                <span
                    class="relative flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-300/10 shadow-lg shadow-cyan-500/20"
                >
                    <span
                        class="absolute h-5 w-5 rounded-full border border-cyan-200/70"
                    ></span>
                    <span
                        class="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_18px_rgba(251,191,36,0.9)]"
                    ></span>
                </span>
                <span>
                    <span
                        class="block text-sm font-semibold tracking-[0.22em] text-cyan-100 uppercase"
                        >{{ appName }}</span
                    >
                    <span class="block text-xs text-slate-400">{{
                        __('frontend.welcome.brand_subtitle')
                    }}</span>
                </span>
            </Link>

            <div class="relative">
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-200 shadow-2xl shadow-black/20 backdrop-blur-xl transition-colors duration-200 hover:border-cyan-300/40 hover:bg-cyan-300/10 focus-visible:ring-2 focus-visible:ring-cyan-300/80 focus-visible:outline-none"
                    :aria-expanded="localeMenuOpen"
                    aria-haspopup="menu"
                    @click="localeMenuOpen = !localeMenuOpen"
                >
                    <FlagIcon :code="currentLocaleOption.flag" size="S" />
                    <span class="hidden sm:inline">{{
                        currentLocaleOption.label
                    }}</span>
                    <svg
                        class="h-4 w-4 text-slate-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>

                <div
                    v-if="localeMenuOpen"
                    class="absolute right-0 mt-3 w-52 overflow-hidden rounded-2xl border border-white/10 bg-slate-950/95 p-2 shadow-2xl shadow-black/40 backdrop-blur-xl"
                    role="menu"
                >
                    <button
                        v-for="locale in availableLocales"
                        :key="locale.code"
                        type="button"
                        class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2 text-left text-sm text-slate-200 transition-colors duration-200 hover:bg-cyan-300/10 focus-visible:ring-2 focus-visible:ring-cyan-300/70 focus-visible:outline-none"
                        :class="{
                            'bg-cyan-300/10 text-cyan-100':
                                locale.code === currentLocale,
                        }"
                        role="menuitem"
                        @click="switchLocale(locale.code)"
                    >
                        <FlagIcon :code="locale.flag" size="S" />
                        <span>{{ locale.label }}</span>
                    </button>
                </div>
            </div>
        </header>

        <section
            class="relative mx-auto grid w-full max-w-7xl items-center gap-12 px-5 pt-10 pb-20 sm:px-8 sm:pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:pt-20 lg:pb-28"
        >
            <div class="max-w-3xl">
                <div
                    class="inline-flex items-center gap-3 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-xs font-semibold tracking-[0.22em] text-cyan-100 uppercase shadow-lg shadow-cyan-500/10 backdrop-blur-xl"
                >
                    <span
                        class="h-2 w-2 animate-pulse rounded-full bg-cyan-300 shadow-[0_0_16px_rgba(103,232,249,0.9)] motion-reduce:animate-none"
                    ></span>
                    {{ __('frontend.welcome.badge_crypto_billing_api') }}
                </div>

                <h1
                    class="mt-8 max-w-4xl text-5xl leading-[0.95] font-semibold tracking-[-0.06em] text-balance text-white sm:text-6xl lg:text-7xl"
                >
                    {{ __('frontend.welcome.hero_title') }}
                    <span
                        class="bg-gradient-to-r from-cyan-200 via-violet-200 to-amber-200 bg-clip-text text-transparent"
                    >
                        {{ __('frontend.welcome.hero_title_accent') }}
                    </span>
                </h1>

                <p
                    class="mt-6 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl"
                >
                    {{ __('frontend.welcome.description') }}
                </p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="group inline-flex cursor-pointer items-center justify-center gap-2 rounded-full bg-cyan-300 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-2xl shadow-cyan-500/25 transition-colors duration-200 hover:bg-cyan-200 focus-visible:ring-2 focus-visible:ring-cyan-200 focus-visible:ring-offset-2 focus-visible:ring-offset-[#030712] focus-visible:outline-none"
                    >
                        {{ __('frontend.welcome.go_dashboard') }}
                        <span
                            aria-hidden="true"
                            class="transition-transform duration-200 group-hover:translate-x-0.5 motion-reduce:transition-none"
                            >-></span
                        >
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex cursor-pointer items-center justify-center rounded-full bg-cyan-300 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-2xl shadow-cyan-500/25 transition-colors duration-200 hover:bg-cyan-200 focus-visible:ring-2 focus-visible:ring-cyan-200 focus-visible:ring-offset-2 focus-visible:ring-offset-[#030712] focus-visible:outline-none"
                        >
                            {{ __('frontend.welcome.login') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="inline-flex cursor-pointer items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white shadow-2xl shadow-black/20 backdrop-blur-xl transition-colors duration-200 hover:border-violet-300/50 hover:bg-violet-300/10 focus-visible:ring-2 focus-visible:ring-violet-300/80 focus-visible:outline-none"
                        >
                            {{ __('frontend.welcome.register') }}
                        </Link>
                    </template>
                </div>

                <dl class="mt-12 grid max-w-2xl grid-cols-3 gap-3">
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 backdrop-blur-xl"
                    >
                        <dt class="text-xs leading-5 text-slate-400">
                            {{ stat.label }}
                        </dt>
                        <dd class="mt-1 text-xl font-semibold text-white">
                            {{ stat.value }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="relative isolate overflow-hidden rounded-[2rem]">
                <div
                    class="absolute inset-0 rounded-[2rem] bg-gradient-to-br from-cyan-400/20 via-violet-500/20 to-amber-400/10 blur-2xl"
                ></div>
                <div
                    class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-slate-950/70 p-5 shadow-2xl shadow-black/40 backdrop-blur-2xl"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-200/60 to-transparent"
                    ></div>

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-[0.2em] text-cyan-200 uppercase"
                            >
                                {{ __('frontend.welcome.terminal_label') }}
                            </p>
                            <h2 class="mt-2 text-xl font-semibold text-white">
                                {{ __('frontend.welcome.cta_title') }}
                            </h2>
                        </div>
                        <div
                            class="shrink-0 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-semibold text-emerald-200"
                        >
                            {{ __('frontend.welcome.live_status') }}
                        </div>
                    </div>

                    <div
                        class="mt-6 rounded-3xl border border-white/10 bg-black/30 p-4 font-mono text-sm text-slate-300"
                    >
                        <div
                            class="flex items-center gap-2 border-b border-white/10 pb-3"
                        >
                            <span
                                class="h-3 w-3 rounded-full bg-rose-400/80"
                            ></span>
                            <span
                                class="h-3 w-3 rounded-full bg-amber-300/80"
                            ></span>
                            <span
                                class="h-3 w-3 rounded-full bg-emerald-300/80"
                            ></span>
                            <span class="ml-3 text-xs text-slate-500"
                                >payment.gateway</span
                            >
                        </div>
                        <div class="space-y-3 pt-4">
                            <p>
                                <span class="text-cyan-300">POST</span>
                                /api/invoices
                            </p>
                            <p class="text-slate-500">
                                { amount: "250.00", currency: "USDT", network:
                                "tron" }
                            </p>
                            <p>
                                <span class="text-emerald-300">200</span>
                                invoice.created
                            </p>
                            <p>
                                <span class="text-amber-200">webhook</span>
                                status_changed -> paid
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-4"
                        >
                            <p class="text-xs text-slate-400">
                                {{ __('frontend.welcome.routing_label') }}
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-white">
                                Multi-chain
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="network in networks"
                                    :key="network"
                                    class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-2.5 py-1 text-xs text-cyan-100"
                                >
                                    {{ network }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-4"
                        >
                            <p class="text-xs text-slate-400">
                                {{ __('frontend.welcome.settlement_label') }}
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-white">
                                Stable API
                            </p>
                            <div
                                class="mt-3 h-2 overflow-hidden rounded-full bg-white/10"
                            >
                                <div
                                    class="h-full w-[86%] rounded-full bg-gradient-to-r from-cyan-300 via-violet-300 to-amber-200"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="relative mx-auto grid w-full max-w-7xl gap-4 px-5 pb-16 sm:px-8 lg:grid-cols-3 lg:px-10 lg:pb-24"
        >
            <article
                v-for="feature in features"
                :key="feature.title"
                class="group rounded-3xl border border-white/10 bg-white/[0.035] p-6 shadow-2xl shadow-black/20 backdrop-blur-xl transition-colors duration-200 hover:border-cyan-300/30 hover:bg-white/[0.055]"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5"
                >
                    <span
                        class="h-5 w-5 rounded-full bg-gradient-to-br shadow-lg"
                        :class="feature.accent"
                    ></span>
                </div>
                <h3 class="mt-5 text-lg font-semibold text-white">
                    {{ feature.title }}
                </h3>
                <p class="mt-3 leading-7 text-slate-400">
                    {{ feature.description }}
                </p>
            </article>
        </section>

        <footer
            class="relative mx-auto flex w-full max-w-7xl flex-wrap justify-center gap-3 border-t border-white/10 px-5 py-8 text-sm text-slate-500 sm:px-8 lg:px-10"
        >
            <span class="rounded-full border border-white/10 px-3 py-1">{{
                __('frontend.welcome.bullet_api_tokens')
            }}</span>
            <span class="rounded-full border border-white/10 px-3 py-1">{{
                __('frontend.welcome.bullet_invoices_notifications')
            }}</span>
        </footer>
    </main>
</template>
