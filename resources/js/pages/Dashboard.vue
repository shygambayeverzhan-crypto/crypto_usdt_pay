<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart, LineChart, PieChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TooltipComponent,
    type ComposeOption,
} from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import {
    ArrowDownLeft,
    ArrowUpRight,
    Check,
    Clock3,
    FileText,
    Layers3,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import VChart from 'vue-echarts';

use([
    CanvasRenderer,
    BarChart,
    LineChart,
    PieChart,
    GridComponent,
    LegendComponent,
    TooltipComponent,
]);

type ChartPoint = {
    name: string;
    value: number;
};

type TimelinePoint = {
    date: string;
    created: number;
    paid: number;
};

type TurnoverPoint = {
    date: string;
    amount: number;
};

type TurnoverCurrency = {
    currency: string;
    total: number;
    series: TurnoverPoint[];
};

type TopClient = {
    name: string;
    externalId: string | null;
    invoices: number;
    paidInvoices: number;
};

type DashboardChartOption = ComposeOption<any>;

const props = defineProps<{
    stats: {
        totalInvoices: number;
        paidInvoices: number;
        activeInvoices: number;
        expiredInvoices: number;
        cancelledInvoices: number;
        addressesTotal: number;
        successRate: number;
        callbackSuccessRate: number;
    };
    charts: {
        timeline: TimelinePoint[];
        turnoverByCurrency: TurnoverCurrency[];
        statusBreakdown: ChartPoint[];
        networkBreakdown: ChartPoint[];
        topClients: TopClient[];
    };
}>();

const { __ } = vueLang();
const page = usePage();
const isApproved = computed(
    () => (page.props as any)?.auth?.is_approved === true,
);
const hasInvoices = computed(() => props.stats.totalInvoices > 0);
const hasTopClients = computed(() => props.charts.topClients.length > 0);
const hasNetworks = computed(() => props.charts.networkBreakdown.length > 0);
const selectedCurrency = ref(
    props.charts.turnoverByCurrency[0]?.currency ?? '',
);
const selectedTurnover = computed(
    () =>
        props.charts.turnoverByCurrency.find(
            (item) => item.currency === selectedCurrency.value,
        ) ?? props.charts.turnoverByCurrency[0],
);
const hasTurnover = computed(() => props.charts.turnoverByCurrency.length > 0);

const chartTextColor = ref('#a3b4ce');
const chartGridColor = 'rgba(148, 163, 184, 0.16)';
let themeObserver: MutationObserver | undefined;

onMounted(() => {
    const updateChartTheme = () => {
        chartTextColor.value =
            document.documentElement.dataset.theme === 'light'
                ? '#52627c'
                : '#a3b4ce';
    };
    updateChartTheme();
    themeObserver = new MutationObserver(updateChartTheme);
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme'],
    });
});

onBeforeUnmount(() => themeObserver?.disconnect());

const timelineOption = computed<DashboardChartOption>(() => ({
    color: ['#66aaff', '#45c8b1'],
    tooltip: {
        trigger: 'axis',
    },
    legend: {
        top: 0,
        textStyle: {
            color: chartTextColor.value,
        },
    },
    grid: {
        top: 44,
        right: 18,
        bottom: 24,
        left: 32,
    },
    xAxis: {
        type: 'category',
        boundaryGap: false,
        data: props.charts.timeline.map((point) => point.date),
        axisLine: {
            lineStyle: {
                color: chartGridColor,
            },
        },
        axisLabel: {
            color: chartTextColor.value,
        },
    },
    yAxis: {
        type: 'value',
        minInterval: 1,
        splitLine: {
            lineStyle: {
                color: chartGridColor,
            },
        },
        axisLabel: {
            color: chartTextColor.value,
        },
    },
    series: [
        {
            name: __('frontend.dashboard.charts.created'),
            type: 'line',
            smooth: true,
            symbolSize: 7,
            areaStyle: {
                opacity: 0.12,
            },
            data: props.charts.timeline.map((point) => point.created),
        },
        {
            name: __('frontend.dashboard.charts.paid'),
            type: 'line',
            smooth: true,
            symbolSize: 7,
            areaStyle: {
                opacity: 0.08,
            },
            data: props.charts.timeline.map((point) => point.paid),
        },
    ],
}));

const statusOption = computed<DashboardChartOption>(() => ({
    color: ['#66aaff', '#c0a3f3', '#45c8b1', '#e9b267', '#8c9db8'],
    tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} ({d}%)',
    },
    legend: {
        bottom: 0,
        textStyle: {
            color: chartTextColor.value,
        },
    },
    series: [
        {
            name: __('frontend.dashboard.charts.statuses'),
            type: 'pie',
            radius: ['48%', '72%'],
            center: ['50%', '44%'],
            avoidLabelOverlap: true,
            label: {
                show: false,
            },
            emphasis: {
                label: {
                    show: true,
                    fontSize: 14,
                    fontWeight: 'bold',
                },
            },
            data: props.charts.statusBreakdown,
        },
    ],
}));

const networkOption = computed<DashboardChartOption>(() => ({
    color: ['#66aaff'],
    tooltip: {
        trigger: 'axis',
        axisPointer: {
            type: 'shadow',
        },
    },
    grid: {
        top: 12,
        right: 18,
        bottom: 28,
        left: 36,
    },
    xAxis: {
        type: 'category',
        data: props.charts.networkBreakdown.map((point) => point.name),
        axisLine: {
            lineStyle: {
                color: chartGridColor,
            },
        },
        axisLabel: {
            color: chartTextColor.value,
        },
    },
    yAxis: {
        type: 'value',
        minInterval: 1,
        splitLine: {
            lineStyle: {
                color: chartGridColor,
            },
        },
        axisLabel: {
            color: chartTextColor.value,
        },
    },
    series: [
        {
            name: __('frontend.dashboard.charts.invoices'),
            type: 'bar',
            barWidth: '42%',
            data: props.charts.networkBreakdown.map((point) => point.value),
        },
    ],
}));

const turnoverOption = computed<DashboardChartOption>(() => ({
    color: ['#45c8b1'],
    tooltip: {
        trigger: 'axis',
        valueFormatter: (value: number) =>
            `${formatAmount(value)} ${selectedTurnover.value?.currency ?? ''}`,
    },
    grid: {
        top: 18,
        right: 18,
        bottom: 28,
        left: 48,
    },
    xAxis: {
        type: 'category',
        boundaryGap: false,
        data: selectedTurnover.value?.series.map((point) => point.date) ?? [],
        axisLine: {
            lineStyle: {
                color: chartGridColor,
            },
        },
        axisLabel: {
            color: chartTextColor.value,
        },
    },
    yAxis: {
        type: 'value',
        splitLine: {
            lineStyle: {
                color: chartGridColor,
            },
        },
        axisLabel: {
            color: chartTextColor.value,
            formatter: (value: number) => formatCompactAmount(value),
        },
    },
    series: [
        {
            name: __('frontend.dashboard.charts.turnover'),
            type: 'line',
            smooth: true,
            symbolSize: 7,
            areaStyle: {
                opacity: 0.16,
            },
            data:
                selectedTurnover.value?.series.map((point) => point.amount) ??
                [],
        },
    ],
}));

const formatAmount = (value: number) =>
    new Intl.NumberFormat(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 6,
    }).format(value);

const formatCompactAmount = (value: number) =>
    new Intl.NumberFormat(undefined, {
        notation: 'compact',
        maximumFractionDigits: 2,
    }).format(value);

const selectCurrency = (currency: string) => {
    selectedCurrency.value = currency;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: __('frontend.nav.dashboard'),
        href: dashboard().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            v-if="!isApproved"
            class="approval-notice alert alert-warning"
            role="status"
        >
            <div class="flex flex-col gap-1">
                <div class="font-semibold">
                    {{ __('frontend.dashboard.pending_approval.title') }}
                </div>
                <div class="text-sm opacity-90">
                    {{ __('frontend.dashboard.pending_approval.description') }}
                </div>
            </div>
        </div>

        <div class="dashboard-space">
            <section class="glass-panel dashboard-hero">
                <div class="hero-copy">
                    <p class="glass-kicker hero-kicker">
                        <span class="hero-mark" aria-hidden="true"
                            ><Layers3 :size="15"
                        /></span>
                        {{ __('frontend.dashboard.hero.kicker') }}
                    </p>
                    <h1>{{ __('frontend.dashboard.hero.title') }}</h1>
                    <p class="glass-muted hero-description">
                        {{ __('frontend.dashboard.hero.description') }}
                    </p>
                    <div v-if="isApproved" class="hero-actions">
                        <Link
                            href="/invoices"
                            class="hero-action btn btn-primary"
                        >
                            <FileText :size="17" aria-hidden="true" />
                            {{ __('frontend.nav.invoices') }}
                            <ArrowUpRight :size="16" aria-hidden="true" />
                        </Link>
                        <Link
                            href="/addresses"
                            class="hero-action secondary-action btn btn-ghost"
                        >
                            <Wallet :size="17" aria-hidden="true" />
                            {{ __('frontend.nav.addresses') }}
                        </Link>
                    </div>
                </div>

                <div class="hero-rates">
                    <div class="glass-surface rate-card">
                        <span
                            class="rate-icon rate-icon-mint"
                            aria-hidden="true"
                            ><ArrowDownLeft :size="19"
                        /></span>
                        <div class="rate-value">
                            {{ props.stats.successRate }}<span>%</span>
                        </div>
                        <p class="glass-muted">
                            {{ __('frontend.dashboard.stats.success_rate') }}
                        </p>
                    </div>
                    <div class="glass-surface rate-card">
                        <span
                            class="rate-icon rate-icon-blue"
                            aria-hidden="true"
                            ><Check :size="19"
                        /></span>
                        <div class="rate-value">
                            {{ props.stats.callbackSuccessRate }}<span>%</span>
                        </div>
                        <p class="glass-muted">
                            {{
                                __('frontend.dashboard.stats.callback_success')
                            }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="metric-grid"
                :aria-label="__('frontend.dashboard.hero.kicker')"
            >
                <div class="glass-panel metric-card">
                    <div class="metric-heading">
                        <span class="glass-muted">{{
                            __('frontend.dashboard.stats.total')
                        }}</span>
                        <span class="metric-icon metric-blue" aria-hidden="true"
                            ><FileText :size="17"
                        /></span>
                    </div>
                    <div class="metric-value">
                        {{ props.stats.totalInvoices }}
                    </div>
                    <div class="glass-muted metric-description">
                        {{
                            __('frontend.dashboard.stats.active', {
                                count: props.stats.activeInvoices,
                            })
                        }}
                    </div>
                </div>

                <div class="glass-panel metric-card">
                    <div class="metric-heading">
                        <span class="glass-muted">{{
                            __('frontend.dashboard.stats.paid')
                        }}</span>
                        <span class="metric-icon metric-mint" aria-hidden="true"
                            ><Check :size="17"
                        /></span>
                    </div>
                    <div class="metric-value">
                        {{ props.stats.paidInvoices }}
                    </div>
                    <div class="glass-muted metric-description">
                        {{
                            __('frontend.dashboard.stats.success', {
                                rate: props.stats.successRate,
                            })
                        }}
                    </div>
                </div>

                <div class="glass-panel metric-card">
                    <div class="metric-heading">
                        <span class="glass-muted">{{
                            __('frontend.dashboard.stats.expired')
                        }}</span>
                        <span
                            class="metric-icon metric-amber"
                            aria-hidden="true"
                            ><Clock3 :size="17"
                        /></span>
                    </div>
                    <div class="metric-value">
                        {{ props.stats.expiredInvoices }}
                    </div>
                    <div class="glass-muted metric-description">
                        {{
                            __('frontend.dashboard.stats.addresses', {
                                count: props.stats.addressesTotal,
                            })
                        }}
                    </div>
                </div>

                <div class="glass-panel metric-card">
                    <div class="metric-heading">
                        <span class="glass-muted">{{
                            __('frontend.dashboard.stats.cancelled')
                        }}</span>
                        <span
                            class="metric-icon metric-lilac"
                            aria-hidden="true"
                            ><X :size="17"
                        /></span>
                    </div>
                    <div class="metric-value">
                        {{ props.stats.cancelledInvoices }}
                    </div>
                    <div class="glass-muted metric-description">
                        {{ __('frontend.dashboard.stats.finalized') }}
                    </div>
                </div>
            </section>

            <div v-if="!hasInvoices" class="glass-panel analytics-empty">
                <div class="empty-illustration" aria-hidden="true">
                    <span class="empty-orbit empty-orbit-outer"></span>
                    <span class="empty-orbit empty-orbit-inner"></span>
                    <span class="empty-orbit-dot"></span>
                    <span class="empty-file empty-file-back"></span>
                    <span class="empty-file empty-file-front"
                        ><Layers3 :size="34" :stroke-width="1.2"
                    /></span>
                </div>
                <div class="empty-copy">
                    <p class="glass-kicker">
                        {{ __('frontend.dashboard.charts.timeline_title') }}
                    </p>
                    <h2>
                        {{ __('frontend.dashboard.empty.title') }}
                    </h2>
                    <p class="glass-muted empty-description">
                        {{ __('frontend.dashboard.empty.description') }}
                    </p>
                    <Link v-if="isApproved" href="/api" class="empty-link">
                        {{ __('frontend.nav.api_docs') }}
                        <ArrowUpRight :size="16" aria-hidden="true" />
                    </Link>
                </div>
            </div>

            <div v-else class="chart-grid grid gap-5 xl:grid-cols-3">
                <div class="glass-panel chart-card card xl:col-span-3">
                    <div class="card-body gap-4">
                        <div>
                            <h2 class="card-title">
                                {{
                                    __(
                                        'frontend.dashboard.charts.timeline_title',
                                    )
                                }}
                            </h2>
                            <p class="text-sm text-base-content/60">
                                {{
                                    __(
                                        'frontend.dashboard.charts.timeline_description',
                                    )
                                }}
                            </p>
                        </div>
                        <VChart
                            class="h-80 min-h-80 w-full"
                            :option="timelineOption"
                            autoresize
                        />
                    </div>
                </div>

                <div class="glass-panel chart-card card xl:col-span-2">
                    <div class="card-body gap-4">
                        <div
                            class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                        >
                            <div>
                                <h2 class="card-title">
                                    {{
                                        __(
                                            'frontend.dashboard.charts.turnover_title',
                                        )
                                    }}
                                </h2>
                                <p class="text-sm text-base-content/60">
                                    {{
                                        __(
                                            'frontend.dashboard.charts.turnover_description',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="hasTurnover"
                                class="flex flex-col gap-2 md:items-end"
                            >
                                <div
                                    class="text-xs tracking-wide text-base-content/50 uppercase"
                                >
                                    {{
                                        __(
                                            'frontend.dashboard.charts.total_turnover',
                                        )
                                    }}
                                </div>
                                <div class="text-2xl font-bold text-success">
                                    {{
                                        formatAmount(
                                            selectedTurnover?.total ?? 0,
                                        )
                                    }}
                                    {{ selectedTurnover?.currency }}
                                </div>
                                <div class="join">
                                    <button
                                        v-for="item in props.charts
                                            .turnoverByCurrency"
                                        :key="item.currency"
                                        type="button"
                                        class="btn join-item btn-sm"
                                        :class="{
                                            'btn-primary':
                                                item.currency ===
                                                selectedCurrency,
                                        }"
                                        @click="selectCurrency(item.currency)"
                                    >
                                        {{ item.currency }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <VChart
                            v-if="hasTurnover"
                            class="h-80 min-h-80 w-full"
                            :option="turnoverOption"
                            autoresize
                        />
                        <div
                            v-else
                            class="flex h-80 items-center justify-center rounded-2xl bg-base-200 text-sm text-base-content/50"
                        >
                            {{ __('frontend.dashboard.empty.no_turnover') }}
                        </div>
                    </div>
                </div>

                <div class="glass-panel chart-card card">
                    <div class="card-body gap-4">
                        <div>
                            <h2 class="card-title">
                                {{
                                    __('frontend.dashboard.charts.status_title')
                                }}
                            </h2>
                            <p class="text-sm text-base-content/60">
                                {{
                                    __(
                                        'frontend.dashboard.charts.status_description',
                                    )
                                }}
                            </p>
                        </div>
                        <VChart
                            class="h-80 min-h-80 w-full"
                            :option="statusOption"
                            autoresize
                        />
                    </div>
                </div>

                <div class="glass-panel chart-card card">
                    <div class="card-body gap-4">
                        <div>
                            <h2 class="card-title">
                                {{
                                    __(
                                        'frontend.dashboard.charts.network_title',
                                    )
                                }}
                            </h2>
                            <p class="text-sm text-base-content/60">
                                {{
                                    __(
                                        'frontend.dashboard.charts.network_description',
                                    )
                                }}
                            </p>
                        </div>
                        <VChart
                            v-if="hasNetworks"
                            class="h-72 min-h-72 w-full"
                            :option="networkOption"
                            autoresize
                        />
                        <div
                            v-else
                            class="flex h-72 items-center justify-center rounded-2xl bg-base-200 text-sm text-base-content/50"
                        >
                            {{ __('frontend.dashboard.empty.no_data') }}
                        </div>
                    </div>
                </div>

                <div class="glass-panel chart-card card xl:col-span-2">
                    <div class="card-body gap-4">
                        <div>
                            <h2 class="card-title">
                                {{
                                    __(
                                        'frontend.dashboard.charts.clients_title',
                                    )
                                }}
                            </h2>
                            <p class="text-sm text-base-content/60">
                                {{
                                    __(
                                        'frontend.dashboard.charts.clients_description',
                                    )
                                }}
                            </p>
                        </div>

                        <div v-if="hasTopClients" class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            {{
                                                __(
                                                    'frontend.dashboard.clients_table.client',
                                                )
                                            }}
                                        </th>
                                        <th>
                                            {{
                                                __(
                                                    'frontend.dashboard.clients_table.external_id',
                                                )
                                            }}
                                        </th>
                                        <th class="text-right">
                                            {{
                                                __(
                                                    'frontend.dashboard.clients_table.invoices',
                                                )
                                            }}
                                        </th>
                                        <th class="text-right">
                                            {{
                                                __(
                                                    'frontend.dashboard.clients_table.paid_invoices',
                                                )
                                            }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="client in props.charts
                                            .topClients"
                                        :key="client.externalId ?? client.name"
                                    >
                                        <td class="font-medium">
                                            {{ client.name }}
                                        </td>
                                        <td class="text-base-content/60">
                                            {{
                                                client.externalId ??
                                                __(
                                                    'frontend.dashboard.clients_table.empty_external_id',
                                                )
                                            }}
                                        </td>
                                        <td class="text-right">
                                            {{ client.invoices }}
                                        </td>
                                        <td class="text-right text-success">
                                            {{ client.paidInvoices }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-else
                            class="flex h-72 items-center justify-center rounded-2xl bg-base-200 text-sm text-base-content/50"
                        >
                            {{ __('frontend.dashboard.empty.no_clients') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard-space {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 22px;
}

.approval-notice {
    margin-bottom: 22px;
    border-radius: 20px;
}

.dashboard-hero {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(270px, 0.65fr);
    align-items: center;
    gap: 36px;
    overflow: hidden;
    padding: clamp(25px, 3.3vw, 44px);
    border-radius: 30px;
    isolation: isolate;
}

.dashboard-hero::before {
    position: absolute;
    z-index: -1;
    inset: 0;
    background:
        radial-gradient(
            ellipse at 90% 12%,
            rgb(118 128 240 / 0.13),
            transparent 62%
        ),
        radial-gradient(
            ellipse at 0% 100%,
            rgb(87 180 248 / 0.08),
            transparent 65%
        );
    content: '';
    pointer-events: none;
}

.hero-copy {
    min-width: 0;
}

.hero-kicker {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.hero-mark {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    border: 1px solid rgb(136 187 255 / 0.28);
    border-radius: 9px;
    background: rgb(128 179 248 / 0.1);
}

.hero-copy h1 {
    max-width: 620px;
    font-size: clamp(29px, 3.1vw, 46px);
    font-weight: 650;
    line-height: 1.13;
    letter-spacing: -0.055em;
}

.hero-description {
    max-width: 520px;
    margin-top: 14px;
    font-size: 14px;
    line-height: 1.75;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 26px;
}

.hero-action {
    min-height: 44px;
    height: auto;
    gap: 9px;
    padding: 12px 18px;
    border-radius: 15px;
    font-size: 13px;
}

.secondary-action {
    border: 1px solid
        color-mix(in srgb, var(--color-base-content) 12%, transparent);
    background: color-mix(in srgb, var(--color-base-content) 4%, transparent);
}

.hero-rates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.rate-card {
    min-width: 0;
    padding: 22px 18px;
    border-radius: 24px;
}

.rate-icon,
.metric-icon {
    display: grid;
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid currentColor;
    border-color: color-mix(in srgb, currentColor 16%, transparent);
    border-radius: 11px;
    background: color-mix(in srgb, currentColor 9%, transparent);
}

.rate-icon-mint,
.metric-mint {
    color: #5ed8bd;
}

.rate-icon-blue,
.metric-blue {
    color: #88baff;
}

.metric-amber {
    color: #e6bc80;
}

.metric-lilac {
    color: #bdabed;
}

.rate-value {
    margin-top: 27px;
    font-size: clamp(30px, 3vw, 42px);
    font-weight: 600;
    line-height: 1;
    letter-spacing: -0.055em;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.rate-value span {
    margin-left: 3px;
    font-size: 0.55em;
    font-weight: 450;
    opacity: 0.65;
}

.rate-card p {
    margin-top: 12px;
    font-size: 12px;
    line-height: 1.45;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.metric-card {
    min-width: 0;
    padding: 22px;
    border-radius: 25px;
}

.metric-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 12px;
    line-height: 1.4;
}

.metric-value {
    margin-top: 20px;
    font-size: 35px;
    font-weight: 600;
    line-height: 1;
    letter-spacing: -0.045em;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.metric-description {
    margin-top: 12px;
    font-size: 11px;
    line-height: 1.5;
}

.analytics-empty {
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: center;
    gap: 28px;
    padding: 35px 40px;
    border-radius: 30px;
}

.empty-illustration {
    position: relative;
    display: grid;
    flex: 0 0 204px;
    width: 204px;
    height: 204px;
    place-items: center;
}

.empty-orbit {
    position: absolute;
    border: 1px solid rgb(128 176 241 / 0.12);
    border-radius: 50%;
}

.empty-orbit-outer {
    inset: 2px;
}

.empty-orbit-inner {
    inset: 28px;
    background: radial-gradient(
        circle,
        rgb(110 163 242 / 0.09),
        transparent 70%
    );
}

.empty-orbit-dot {
    position: absolute;
    top: 36px;
    right: 22px;
    width: 9px;
    height: 9px;
    border: 1px solid rgb(193 217 255 / 0.7);
    border-radius: 50%;
    background: #8bb4ee;
    box-shadow: 0 0 20px rgb(104 173 255 / 0.35);
}

.empty-file {
    position: absolute;
    display: grid;
    width: 82px;
    height: 100px;
    place-items: center;
    border: 1px solid rgb(174 204 255 / 0.29);
    border-radius: 22px;
}

.empty-file-back {
    background: linear-gradient(
        150deg,
        rgb(166 143 235 / 0.12),
        rgb(145 168 243 / 0.03)
    );
    transform: translate(-17px, -9px) rotate(-15deg);
}

.empty-file-front {
    color: #a9ceff;
    background: linear-gradient(
        145deg,
        rgb(145 190 255 / 0.2),
        rgb(145 168 243 / 0.05)
    );
    box-shadow:
        inset 0 1px 0 rgb(255 255 255 / 0.15),
        0 18px 36px rgb(2 11 31 / 0.12);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    transform: translate(12px, 8px) rotate(9deg);
}

.empty-copy {
    max-width: 470px;
}

.empty-copy h2 {
    margin-top: 12px;
    font-size: 23px;
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: -0.035em;
}

.empty-description {
    margin-top: 12px;
    font-size: 13px;
    line-height: 1.8;
}

.empty-link {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    border-radius: 8px;
    color: var(--color-primary);
    font-size: 13px;
    font-weight: 550;
}

.empty-link:hover {
    text-decoration: underline;
    text-underline-offset: 4px;
}

.chart-grid,
.chart-card,
.chart-card .card-body {
    min-width: 0;
}

.chart-card {
    border-radius: 26px;
}

.chart-card .card-body {
    padding: 26px;
}

.chart-card .card-title {
    margin-bottom: 8px;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: -0.025em;
}

[data-theme='light'] .rate-icon-mint,
[data-theme='light'] .metric-mint {
    color: #16836d;
}

[data-theme='light'] .rate-icon-blue,
[data-theme='light'] .metric-blue {
    color: #3b6eb3;
}

[data-theme='light'] .metric-amber {
    color: #a26923;
}

[data-theme='light'] .metric-lilac {
    color: #7b60ac;
}

[data-theme='light'] .empty-file-front {
    color: #557bb4;
}

@media (max-width: 1199px) {
    .dashboard-hero {
        grid-template-columns: minmax(0, 1fr);
        gap: 25px;
    }

    .hero-rates {
        max-width: 460px;
    }

    .rate-card {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 8px 14px;
        align-items: center;
        padding: 18px;
    }

    .rate-value {
        margin-top: 0;
    }

    .rate-card p {
        grid-column: 1 / -1;
        margin-top: 3px;
    }

    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 599px) {
    .dashboard-space {
        gap: 16px;
    }

    .dashboard-hero {
        gap: 24px;
        padding: 25px 22px;
        border-radius: 25px;
    }

    .hero-kicker {
        margin-bottom: 17px;
    }

    .hero-copy h1 {
        font-size: 31px;
    }

    .hero-description {
        font-size: 13px;
    }

    .hero-actions {
        gap: 8px;
        margin-top: 22px;
    }

    .hero-action {
        padding: 12px 14px;
    }

    .rate-card {
        grid-template-columns: 26px minmax(0, 1fr);
        gap: 10px;
        padding: 15px 12px;
        border-radius: 18px;
    }

    .rate-icon {
        width: 26px;
        height: 28px;
        border-radius: 9px;
    }

    .rate-value {
        font-size: 29px;
    }

    .rate-card p {
        font-size: 11px;
    }

    .metric-grid {
        gap: 12px;
    }

    .metric-card {
        padding: 17px 15px;
        border-radius: 21px;
    }

    .metric-heading {
        min-height: 34px;
        font-size: 11px;
    }

    .metric-icon {
        width: 28px;
        height: 30px;
        border-radius: 10px;
    }

    .metric-value {
        font-size: 32px;
    }

    .metric-description {
        font-size: 10px;
    }

    .analytics-empty {
        flex-direction: column;
        gap: 4px;
        padding: 22px 25px 30px;
        border-radius: 25px;
        text-align: center;
    }

    .empty-illustration {
        flex-basis: 180px;
        width: 180px;
        height: 180px;
    }

    .empty-copy h2 {
        font-size: 22px;
    }

    .chart-card .card-body {
        padding: 20px 16px;
    }
}
</style>
