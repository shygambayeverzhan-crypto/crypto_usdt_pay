<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head } from '@inertiajs/vue3';
import Pagination from '@/components/ui/Pagination.vue';
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue';
import type { BreadcrumbItem } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';

type PaginationLink = { url: string | null; label: string; active: boolean };

interface GeoPayload {
    country?: { name?: string | null; code?: string | null; flag?: string | null; emoji?: string | null } | null;
    location?: { city?: string | null; state?: string | null; latitude?: number | null; longitude?: number | null } | null;
    currency?: { code?: string | null; name?: string | null; symbol?: string | null } | null;
}

interface LoginItem {
    id: number;
    ip_address: string | null;
    device_type: string | null;
    platform: string | null;
    browser: string | null;
    geo_status: 'ok' | 'skipped' | 'limited' | 'failed' | string;
    geo: GeoPayload | null;
    created_at: string | null;
}

interface PaginatedLogins {
    data: LoginItem[];
    links: PaginationLink[];
}

interface Props {
    logins: PaginatedLogins;
}

const props = defineProps<Props>();
const { __ } = vueLang();

const breadcrumbs: BreadcrumbItem[] = [
    { title: __('frontend.login_history.breadcrumb'), href: '/settings/login-history' },
];

function locationLabel(login: LoginItem): string {
    const country = login.geo?.country?.name;
    const city = login.geo?.location?.city;
    const state = login.geo?.location?.state;
    if (city && country) return `${city}, ${country}`;
    if (state && country) return `${state}, ${country}`;
    if (country) return country;
    return __('frontend.login_history.table.ip');
}

function statusBadge(login: LoginItem) {
    switch (login.geo_status) {
    case 'ok':
        return { text: __('frontend.login_history.status.ok'), class: 'badge-success' };
    case 'limited':
        return { text: __('frontend.login_history.status.limited'), class: 'badge-warning' };
    case 'failed':
        return { text: __('frontend.login_history.status.failed'), class: 'badge-error' };
    case 'skipped':
    default:
        return { text: __('frontend.login_history.status.skipped'), class: 'badge-ghost' };
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="__('frontend.login_history.breadcrumb')" />
        <SettingsLayout>
            <div class="space-y-6">
                <div class="space-y-1">
                    <h2 class="text-lg font-medium">{{ __('frontend.login_history.title') }}</h2>
                    <p class="text-sm text-base-content/70">
                        {{ __('frontend.login_history.description') }}
                    </p>
                </div>

                <div>
                    <div>
                        <!-- Desktop table -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="table table-sm w-full">
                                <thead>
                                    <tr>
                                        <th>{{ __('frontend.login_history.table.location') }}</th>
                                        <th>{{ __('frontend.login_history.table.device') }}</th>
                                        <th>{{ __('frontend.login_history.table.time') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="login in props.logins.data" :key="login.id">
                                        <td class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <img
                                                    v-if="login.geo?.country?.flag"
                                                    :src="login.geo.country.flag"
                                                    :alt="login.geo.country.name || 'flag'"
                                                    class="w-5 h-4 rounded border border-base-200"
                                                >
                                                <span v-else-if="login.geo?.country?.emoji" class="text-lg leading-none">
                                                    {{ login.geo.country.emoji }}
                                                </span>
                                                <span class="text-sm">
                                                    {{ locationLabel(login) }}
                                                </span>
                                            </div>
                                            <p class="text-xs opacity-70">
                                                {{ login.ip_address || __('frontend.login_history.table.ip') }}
                                            </p>
                                        </td>
                                        <td>
                                            <div class="flex flex-col text-sm">
                                                <span>{{ login.platform || __('frontend.login_history.table.unknown_platform') }}</span>
                                                <span class="opacity-70 text-xs">
                                                    {{ [login.browser, login.device_type].filter(Boolean).join(' · ') || __('frontend.login_history.table.ip') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <DateTimeFormat v-if="login.created_at" :value="login.created_at" short-year hide-seconds />
                                            <span v-else class="opacity-60">{{ __('frontend.login_history.table.ip') }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!props.logins.data.length">
                                        <td colspan="5" class="text-center text-sm opacity-70 py-6">
                                            {{ __('frontend.login_history.table.empty') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile cards -->
                        <div class="lg:hidden space-y-3">
                            <div
                                v-for="login in props.logins.data"
                                :key="login.id"
                                class="card bg-base-100 shadow-sm border border-base-200"
                            >
                                <div class="card-body p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-mono text-xs">{{ login.ip_address || __('frontend.login_history.table.ip') }}</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <img
                                                v-if="login.geo?.country?.flag"
                                                :src="login.geo.country.flag"
                                                :alt="login.geo.country.name || 'flag'"
                                                class="w-5 h-4 rounded border border-base-200"
                                            >
                                            <span v-else-if="login.geo?.country?.emoji" class="text-lg leading-none">
                                                {{ login.geo.country.emoji }}
                                            </span>
                                            <span class="text-sm font-medium">
                                                {{ locationLabel(login) }}
                                            </span>
                                        </div>
                                        <p class="text-xs opacity-70">
                                            {{ [login.platform, login.browser, login.device_type].filter(Boolean).join(' · ') || __('frontend.login_history.table.unknown_device') }}
                                        </p>
                                    </div>

                                    <div class="text-xs opacity-70">
                                        <DateTimeFormat short-year v-if="login.created_at" :value="login.created_at" hide-seconds />
                                        <span v-else>{{ __('frontend.login_history.table.ip') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!props.logins.data.length" class="text-center text-sm opacity-70 py-6">
                                {{ __('frontend.login_history.table.empty') }}
                            </div>
                        </div>

                        <Pagination :links="props.logins.links" />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

