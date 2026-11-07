<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';
import { usePage } from '@inertiajs/vue3';
import { BarChart, LineChart, PieChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TooltipComponent,
    type ComposeOption,
} from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { computed, ref } from 'vue';
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

const chartTextColor = '#64748b';
const chartGridColor = 'rgba(148, 163, 184, 0.22)';

const timelineOption = computed<DashboardChartOption>(() => ({
    color: ['#6366f1', '#22c55e'],
    tooltip: {
        trigger: 'axis',
    },
    legend: {
        top: 0,
        textStyle: {
            color: chartTextColor,
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
            color: chartTextColor,
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
            color: chartTextColor,
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
    color: ['#6366f1', '#f59e0b', '#22c55e', '#f97316', '#94a3b8'],
    tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} ({d}%)',
    },
    legend: {
        bottom: 0,
        textStyle: {
            color: chartTextColor,
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
    color: ['#0ea5e9'],
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
            color: chartTextColor,
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
            color: chartTextColor,
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
    color: ['#22c55e'],
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
            color: chartTextColor,
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
            color: chartTextColor,
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
        <div v-if="!isApproved" class="alert alert-warning">
            <div class="flex flex-col gap-1">
                <div class="font-semibold">
                    {{ __('frontend.dashboard.pending_approval.title') }}
                </div>
                <div class="text-sm opacity-90">
                    {{ __('frontend.dashboard.pending_approval.description') }}
                </div>
            </div>
        </div>

        <div class="mt-6 flex h-full flex-1 flex-col gap-6">
            <div
                class="rounded-3xl border border-base-300 bg-gradient-to-br from-base-100 via-base-100 to-primary/10 p-6 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-primary">
                            {{ __('frontend.dashboard.hero.kicker') }}
                        </p>
                        <h1
                            class="text-2xl font-bold tracking-tight md:text-3xl"
                        >
                            {{ __('frontend.dashboard.hero.title') }}
                        </h1>
                        <p class="max-w-2xl text-sm text-base-content/65">
                            {{ __('frontend.dashboard.hero.description') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:min-w-80">
                        <div class="rounded-2xl bg-base-100/80 p-4 shadow-sm">
                            <div
                                class="text-xs tracking-wide text-base-content/50 uppercase"
                            >
                                {{
                                    __('frontend.dashboard.stats.success_rate')
                                }}
                            </div>
                            <div class="mt-1 text-3xl font-bold text-success">
                                {{ props.stats.successRate }}%
                            </div>
                        </div>
                        <div class="rounded-2xl bg-base-100/80 p-4 shadow-sm">
                            <div
                                class="text-xs tracking-wide text-base-content/50 uppercase"
                            >
                                {{
                                    __(
                                        'frontend.dashboard.stats.callback_success',
                                    )
                                }}
                            </div>
                            <div class="mt-1 text-3xl font-bold text-info">
                                {{ props.stats.callbackSuccessRate }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="stats stats-vertical overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm md:stats-horizontal"
            >
                <div class="stat">
                    <div class="stat-title">
                        {{ __('frontend.dashboard.stats.total') }}
                    </div>
                    <div class="stat-value">
                        {{ props.stats.totalInvoices }}
                    </div>
                    <div class="stat-desc">
                        {{
                            __('frontend.dashboard.stats.active', {
                                count: props.stats.activeInvoices,
                            })
                        }}
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-title">
                        {{ __('frontend.dashboard.stats.paid') }}
                    </div>
                    <div class="stat-value text-success">
                        {{ props.stats.paidInvoices }}
                    </div>
                    <div class="stat-desc">
                        {{
                            __('frontend.dashboard.stats.success', {
                                rate: props.stats.successRate,
                            })
                        }}
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-title">
                        {{ __('frontend.dashboard.stats.expired') }}
                    </div>
                    <div class="stat-value text-warning">
                        {{ props.stats.expiredInvoices }}
                    </div>
                    <div class="stat-desc">
                        {{
                            __('frontend.dashboard.stats.addresses', {
                                count: props.stats.addressesTotal,
                            })
                        }}
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-title">
                        {{ __('frontend.dashboard.stats.cancelled') }}
                    </div>
                    <div class="stat-value text-base-content/70">
                        {{ props.stats.cancelledInvoices }}
                    </div>
                    <div class="stat-desc">
                        {{ __('frontend.dashboard.stats.finalized') }}
                    </div>
                </div>
            </div>

            <div
                v-if="!hasInvoices"
                class="card border border-dashed border-base-300 bg-base-100 shadow-sm"
            >
                <div class="card-body items-center text-center">
                    <h2 class="card-title">
                        {{ __('frontend.dashboard.empty.title') }}
                    </h2>
                    <p class="max-w-xl text-sm text-base-content/60">
                        {{ __('frontend.dashboard.empty.description') }}
                    </p>
                </div>
            </div>

            <div v-else class="grid gap-6 xl:grid-cols-3">
                <div
                    class="card border border-base-300 bg-base-100 shadow-sm xl:col-span-3"
                >
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

                <div
                    class="card border border-base-300 bg-base-100 shadow-sm xl:col-span-2"
                >
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

                <div class="card border border-base-300 bg-base-100 shadow-sm">
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

                <div class="card border border-base-300 bg-base-100 shadow-sm">
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

                <div
                    class="card border border-base-300 bg-base-100 shadow-sm xl:col-span-2"
                >
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
