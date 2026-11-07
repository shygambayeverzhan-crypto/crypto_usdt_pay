<script setup lang="ts">
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Alert from '@/components/ui/Alert.vue';
import { vueLang } from '@erag/lang-sync-inertia';
import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue';
import { logout as logoutRoute } from '@/routes';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    title: undefined,
});

const appName = computed(() => (page.props as any)?.name ?? 'Laravel');

const page = usePage();
const impersonationForm = useForm({});
const { __ } = vueLang();

const isDashboardActive = computed(() => page.url === '/dashboard');
const isAddressesActive = computed(() => page.url.startsWith('/addresses'));
const isMerchantsActive = computed(() => page.url.startsWith('/merchants'));
const isClientsActive = computed(() => page.url.startsWith('/clients'));
const isInvoicesActive = computed(() => page.url.startsWith('/invoices'));
const isNotificationsActive = computed(() => page.url.startsWith('/notifications'));
const isCallbackLogsActive = computed(() => page.url.startsWith('/callback-logs'));
const isAppSettingsActive = computed(() => page.url.startsWith('/app-settings'));
const isProfileSettingsActive = computed(() => page.url.startsWith('/settings/profile'));
const isApiDocsActive = computed(() => page.url.startsWith('/api'));
const isAdminUsersActive = computed(() => page.url.startsWith('/admin/users'));
const unreadNotifications = computed(() => (page.props as any)?.notifications?.unread_count ?? 0);

const userEmail = computed(() => (page.props as any)?.auth?.user?.email ?? '');
const isImpersonated = computed(() => (page.props as any)?.auth?.is_impersonated === true);
const isApproved = computed(() => (page.props as any)?.auth?.is_approved === true);
const flashSuccess = computed(() => (page.props as any)?.flash?.success ?? null);
const flashError = computed(() => (page.props as any)?.flash?.error ?? null);

const logoutForm = useForm({});

function leaveImpersonation() {
    impersonationForm.post('/impersonate/leave', { preserveScroll: true });
}

function logout() {
    logoutForm.post(logoutRoute().url, { preserveScroll: true });
}

const pageTitle = computed(() => {
    if (props.title) return props.title;
    if (isDashboardActive.value) return __('frontend.layout.page_titles.dashboard');
    if (isAddressesActive.value) return __('frontend.layout.page_titles.addresses');
    if (isMerchantsActive.value) return __('frontend.layout.page_titles.merchants');
    if (isClientsActive.value) return __('frontend.layout.page_titles.clients');
    if (isInvoicesActive.value) return __('frontend.layout.page_titles.invoices');
    if (isNotificationsActive.value) return __('frontend.layout.page_titles.notifications');
    if (isCallbackLogsActive.value) return __('frontend.layout.page_titles.callback_logs');
    if (isAppSettingsActive.value) return __('frontend.layout.page_titles.app_settings');
    if (isApiDocsActive.value) return __('frontend.layout.page_titles.api_docs');
    if (isAdminUsersActive.value) return __('frontend.layout.page_titles.users');
    return '';
});

const pageIconViewBox = computed(() => (isDashboardActive.value ? '2 0 21 21' : '0 0 24 24'));

const pageIconPath = computed(() => {
    if (isDashboardActive.value) {
        return [
            'M9.918 10.0005H7.082C6.66587 9.99708 6.26541 10.1591 5.96873 10.4509C5.67204 10.7427 5.50343 11.1404 5.5 11.5565V17.4455C5.5077 18.3117 6.21584 19.0078 7.082 19.0005H9.918C10.3341 19.004 10.7346 18.842 11.0313 18.5502C11.328 18.2584 11.4966 17.8607 11.5 17.4445V11.5565C11.4966 11.1404 11.328 10.7427 11.0313 10.4509C10.7346 10.1591 10.3341 9.99708 9.918 10.0005Z',
            'M9.918 4.0006H7.082C6.23326 3.97706 5.52559 4.64492 5.5 5.4936V6.5076C5.52559 7.35629 6.23326 8.02415 7.082 8.0006H9.918C10.7667 8.02415 11.4744 7.35629 11.5 6.5076V5.4936C11.4744 4.64492 10.7667 3.97706 9.918 4.0006Z',
            'M15.082 13.0007H17.917C18.3333 13.0044 18.734 12.8425 19.0309 12.5507C19.3278 12.2588 19.4966 11.861 19.5 11.4447V5.55666C19.4966 5.14054 19.328 4.74282 19.0313 4.45101C18.7346 4.1592 18.3341 3.9972 17.918 4.00066H15.082C14.6659 3.9972 14.2654 4.1592 13.9687 4.45101C13.672 4.74282 13.5034 5.14054 13.5 5.55666V11.4447C13.5034 11.8608 13.672 12.2585 13.9687 12.5503C14.2654 12.8421 14.6659 13.0041 15.082 13.0007Z',
            'M15.082 19.0006H17.917C18.7661 19.0247 19.4744 18.3567 19.5 17.5076V16.4936C19.4744 15.6449 18.7667 14.9771 17.918 15.0006H15.082C14.2333 14.9771 13.5256 15.6449 13.5 16.4936V17.5066C13.525 18.3557 14.2329 19.0241 15.082 19.0006Z',
        ];
    }
    if (isAddressesActive.value) {
        return 'M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3';
    }
    if (isMerchantsActive.value) {
        return 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z';
    }
    if (isClientsActive.value) {
        return 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z';
    }
    if (isInvoicesActive.value) {
        return 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z';
    }
    if (isNotificationsActive.value) {
        return 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0';
    }
    if (isCallbackLogsActive.value) {
        return 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155';
    }
    if (isAppSettingsActive.value) {
        return [
            'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z',
            'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        ];
    }
    if (isApiDocsActive.value) {
        return 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z';
    }
    if (isAdminUsersActive.value) {
        return 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z';
    }
    return null;
});

const pageIconStrokeWidth = computed(() => (isNotificationsActive.value ? 1.5 : 2));
</script>

<template>
    <div class="drawer lg:drawer-open bg-base-200 min-h-screen">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content overflow-x-hidden">
            <div class="lg:hidden sticky top-0 z-10 border-b border-base-200 bg-base-100/60 py-3 backdrop-blur supports-[backdrop-filter]:bg-base-100/60 lg:px-6">
                <div class="flex justify-between items-center navbar">
                    <div class="navbar-start">
                        <label for="app-drawer" class="btn btn-ghost btn-md lg:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </label>
                    </div>
<!--                    <div class="navbar-center w-full">
                        <div class="breadcrumbs text-sm truncate">
                            <ul>
                                <li v-for="(bc, idx) in breadcrumbs" :key="idx">
                                    <a :href="bc.href" v-if="bc.href" class="link link-hover">{{ bc.title }}</a>
                                    <span v-else>{{ bc.title }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="navbar-end"></div>-->
                    <div>
                        <Link
                            v-if="isApproved"
                            href="/settings/profile"
                            :class="{ 'menu-active': isProfileSettingsActive, active: isProfileSettingsActive }"
                            class="btn btn-ghost btn-md w-full justify-start rounded-xl border border-base-300/70 bg-base-200/60 shadow-sm transition hover:border-primary/50 hover:bg-base-200"
                        >
                        <span class="flex items-center gap-3 truncate text-base font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7 opacity-30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            <span class="truncate" v-text="userEmail || __('frontend.nav.profile')" />
                        </span>
                        </Link>
                        <button
                            v-else
                            type="button"
                            class="btn btn-error btn-md w-full justify-start rounded-xl shadow-sm"
                            :disabled="logoutForm.processing"
                            @click="logout"
                        >
                            <span class="flex items-center gap-3 truncate text-base font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7 opacity-30">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                                <span class="truncate">{{ __('frontend.common.logout') }}</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-4 lg:p-6">
                <div v-if="pageTitle" class="flex items-center justify-between gap-4 mb-6">
                    <h1 class="text-xl font-semibold flex items-center gap-2 py-0.5">
                        <svg v-if="pageIconPath" xmlns="http://www.w3.org/2000/svg" fill="none" :viewBox="pageIconViewBox" :stroke-width="pageIconStrokeWidth" stroke="currentColor" class="size-5 opacity-60">
                            <path v-if="typeof pageIconPath === 'string'" stroke-linecap="round" stroke-linejoin="round" :d="pageIconPath" />
                            <template v-else-if="Array.isArray(pageIconPath)">
                                <path
                                    v-for="(path, idx) in pageIconPath"
                                    :key="idx"
                                    :fill-rule="isDashboardActive ? 'evenodd' : undefined"
                                    :clip-rule="isDashboardActive ? 'evenodd' : undefined"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="path"
                                />
                            </template>
                        </svg>
                        {{ pageTitle }}
                    </h1>
                    <slot name="header-actions" />
                </div>
                <div v-if="flashSuccess || flashError" class="mb-4 grid gap-2">
                    <Alert v-if="flashSuccess" type="success" :message="flashSuccess" />
                    <Alert v-if="flashError" type="error" :message="flashError" />
                </div>
                <slot />
            </div>
        </div>
        <div class="drawer-side">
            <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside
                class="sidebar-elevated relative z-[1] flex h-dvh max-h-dvh min-h-0 w-60 flex-col overflow-y-auto overscroll-y-contain border-r border-base-200 bg-base-100 lg:z-20"
            >
                <div class="p-2">
                    <a href="/dashboard" class="block px-2 py-3 text-xl font-semibold tracking-wide text-center">
                        {{ appName }}
                    </a>
                    <div>
                        <ul class="menu w-full font-semibold space-y-1">
                            <li>
                                <Link href="/dashboard" :class="{ 'menu-active': isDashboardActive, active: isDashboardActive }" aria-current="page">
                                    <span class="flex items-center gap-2 font-semibold">
                                        <svg class="size-5 opacity-30" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="2 0 21 21" aria-hidden="true">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.918 10.0005H7.082C6.66587 9.99708 6.26541 10.1591 5.96873 10.4509C5.67204 10.7427 5.50343 11.1404 5.5 11.5565V17.4455C5.5077 18.3117 6.21584 19.0078 7.082 19.0005H9.918C10.3341 19.004 10.7346 18.842 11.0313 18.5502C11.328 18.2584 11.4966 17.8607 11.5 17.4445V11.5565C11.4966 11.1404 11.328 10.7427 11.0313 10.4509C10.7346 10.1591 10.3341 9.99708 9.918 10.0005Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.918 4.0006H7.082C6.23326 3.97706 5.52559 4.64492 5.5 5.4936V6.5076C5.52559 7.35629 6.23326 8.02415 7.082 8.0006H9.918C10.7667 8.02415 11.4744 7.35629 11.5 6.5076V5.4936C11.4744 4.64492 10.7667 3.97706 9.918 4.0006Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.082 13.0007H17.917C18.3333 13.0044 18.734 12.8425 19.0309 12.5507C19.3278 12.2588 19.4966 11.861 19.5 11.4447V5.55666C19.4966 5.14054 19.328 4.74282 19.0313 4.45101C18.7346 4.1592 18.3341 3.9972 17.918 4.00066H15.082C14.6659 3.9972 14.2654 4.1592 13.9687 4.45101C13.672 4.74282 13.5034 5.14054 13.5 5.55666V11.4447C13.5034 11.8608 13.672 12.2585 13.9687 12.5503C14.2654 12.8421 14.6659 13.0041 15.082 13.0007Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.082 19.0006H17.917C18.7661 19.0247 19.4744 18.3567 19.5 17.5076V16.4936C19.4744 15.6449 18.7667 14.9771 17.918 15.0006H15.082C14.2333 14.9771 13.5256 15.6449 13.5 16.4936V17.5066C13.525 18.3557 14.2329 19.0241 15.082 19.0006Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        {{ __('frontend.nav.dashboard') }}
                                    </span>
                                </Link>
                            </li>
           
                            <li v-if="isApproved">
                                <Link href="/merchants" :class="{ 'menu-active': isMerchantsActive, active: isMerchantsActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 opacity-30">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                        </svg>
                                        {{ __('frontend.nav.merchants') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved">
                                <Link href="/invoices" :class="{ 'menu-active': isInvoicesActive, active: isInvoicesActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        {{ __('frontend.nav.invoices') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved">
                                <Link href="/addresses" :class="{ 'menu-active': isAddressesActive, active: isAddressesActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"
                                            />
                                        </svg>
                                        {{ __('frontend.nav.addresses') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved">
                                <Link href="/clients" :class="{ 'menu-active': isClientsActive, active: isClientsActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                        </svg>
                                        {{ __('frontend.nav.clients') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved">
                                <Link href="/notifications" :class="{ 'menu-active': isNotificationsActive, active: isNotificationsActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                        </svg>
                                        <span class="flex items-center gap-2">
                                            {{ __('frontend.nav.notifications') }}
                                            <span v-if="unreadNotifications > 0" class="badge badge-primary badge-xs">
                                                {{ unreadNotifications }}
                                            </span>
                                        </span>
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved">
                                <Link href="/callback-logs" :class="{ 'menu-active': isCallbackLogsActive, active: isCallbackLogsActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                                        </svg>
                                        {{ __('frontend.nav.callback_logs') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved">
                                <Link href="/api" :class="{ 'menu-active': isApiDocsActive, active: isApiDocsActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                                        </svg>
                                        {{ __('frontend.nav.api_docs') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved && (page.props as any)?.auth?.is_admin" class="menu-title">{{ __('frontend.nav.admin') }}</li>
                            <li v-if="isApproved && (page.props as any)?.auth?.is_admin">
                                <Link href="/admin/users" :class="{ 'menu-active': isAdminUsersActive, active: isAdminUsersActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                        {{ __('frontend.nav.users') }}
                                    </span>
                                </Link>
                            </li>
                            <li v-if="isApproved && (page.props as any)?.auth?.is_admin">
                                <Link href="/app-settings" :class="{ 'menu-active': isAppSettingsActive, active: isAppSettingsActive }" aria-current="page">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-30">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        {{ __('frontend.nav.app_settings') }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div v-if="isImpersonated" class="mt-4 rounded-xl border border-warning/50 bg-warning/10 p-3">
                        <div class="text-sm font-semibold text-warning">{{ __('frontend.nav.impersonation_title') }}</div>
                        <p class="text-xs opacity-80 mb-2">{{ __('frontend.nav.impersonation_description') }}</p>
                        <button class="btn btn-warning btn-sm w-full" :disabled="impersonationForm.processing" @click="leaveImpersonation">
                            <span v-if="impersonationForm.processing" class="loading loading-spinner loading-xs mr-2" />
                            {{ __('frontend.nav.impersonation_return') }}
                        </button>
                    </div>
                </div>
                <div class="block lg:hidden mx-4">
                    <div class="flex items-center gap-2 pb-2">
                        <LanguageSwitcher />
                        <ThemeSwitcher />
                    </div>
                </div>
                <div class="hidden lg:block mt-auto border-t border-base-200 p-2 mb-2">
                    <div class="flex items-center gap-2 pb-2">
                        <LanguageSwitcher />
                        <ThemeSwitcher />
                    </div>
                    <Link
                        v-if="isApproved"
                        href="/settings/profile"
                        :class="{ 'menu-active': isProfileSettingsActive, active: isProfileSettingsActive }"
                        class="btn btn-ghost btn-md w-full justify-start rounded-xl border border-base-300/70 bg-base-200/60 shadow-sm transition hover:border-primary/50 hover:bg-base-200"
                    >
                        <span class="flex items-center gap-3 truncate text-base font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 opacity-30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            <span class="truncate" v-text="userEmail || __('frontend.nav.profile')" />
                        </span>
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="btn btn-error btn-md w-full justify-start rounded-xl shadow-sm"
                        :disabled="logoutForm.processing"
                        @click="logout"
                    >
                        <span class="flex items-center gap-3 truncate text-base font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 opacity-30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                            <span class="truncate">{{ __('frontend.common.logout') }}</span>
                        </span>
                    </button>
                </div>
            </aside>
        </div>
    </div>

</template>
