<script setup lang="ts">
import Alert from '@/components/ui/Alert.vue';
import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue';
import { logout as logoutRoute } from '@/routes';
import type { BreadcrumbItemType } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Bell,
    ChevronsUpDown,
    Code2,
    FileText,
    LayoutDashboard,
    LogOut,
    Menu,
    MessageSquare,
    Settings2,
    Store,
    UserRound,
    Users,
    Wallet,
    X,
} from 'lucide-vue-next';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

const props = withDefaults(
    defineProps<{ breadcrumbs?: BreadcrumbItemType[]; title?: string }>(),
    { breadcrumbs: () => [], title: undefined },
);
const page = usePage();
const { __ } = vueLang();
const drawerOpen = ref(false);
const isDesktop = ref(false);
const drawerTrigger = ref<HTMLButtonElement>();
const drawerClose = ref<HTMLButtonElement>();
const sidebar = ref<HTMLElement>();
const isMobileDrawerOpen = computed(() => drawerOpen.value && !isDesktop.value);
let desktopMedia: MediaQueryList | undefined;

function closeDrawer() {
    drawerOpen.value = false;
}

function updateViewport() {
    isDesktop.value = desktopMedia?.matches ?? false;
    if (isDesktop.value) closeDrawer();
}

function handleDrawerKeydown(event: KeyboardEvent) {
    if (!isMobileDrawerOpen.value) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        closeDrawer();
        return;
    }
    if (event.key !== 'Tab' || !sidebar.value) return;

    const focusable = Array.from(
        sidebar.value.querySelectorAll<HTMLElement>(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    ).filter(
        (element) =>
            element.tabIndex >= 0 &&
            !element.matches(':disabled') &&
            element.getClientRects().length > 0 &&
            window.getComputedStyle(element).visibility !== 'hidden',
    );
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (!first || !last) return;
    const focused = document.activeElement;
    const isWithinSidebar = sidebar.value.contains(focused);
    if (event.shiftKey && (!isWithinSidebar || focused === first)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (!isWithinSidebar || focused === last)) {
        event.preventDefault();
        first.focus();
    }
}

watch(isMobileDrawerOpen, async (open, wasOpen) => {
    await nextTick();
    if (open && isMobileDrawerOpen.value) {
        drawerClose.value?.focus();
    } else if (wasOpen) {
        if (isDesktop.value) {
            sidebar.value?.querySelector<HTMLAnchorElement>('a[href]')?.focus();
        } else {
            drawerTrigger.value?.focus();
        }
    }
});

onMounted(() => {
    desktopMedia = window.matchMedia('(min-width: 1024px)');
    updateViewport();
    desktopMedia.addEventListener('change', updateViewport);
    document.addEventListener('keydown', handleDrawerKeydown);
});

onBeforeUnmount(() => {
    desktopMedia?.removeEventListener('change', updateViewport);
    document.removeEventListener('keydown', handleDrawerKeydown);
});

watch(() => page.url, closeDrawer);
const appName = computed(() => {
    const name = (page.props as any)?.name;
    return name && name !== 'Laravel' ? name : 'USDT PAY';
});
const userEmail = computed(() => (page.props as any)?.auth?.user?.email ?? '');
const userName = computed(
    () => (page.props as any)?.auth?.user?.name || userEmail.value,
);
const isApproved = computed(
    () => (page.props as any)?.auth?.is_approved === true,
);
const isAdmin = computed(() => (page.props as any)?.auth?.is_admin === true);
const isImpersonated = computed(
    () => (page.props as any)?.auth?.is_impersonated === true,
);
const unreadNotifications = computed(
    () => (page.props as any)?.notifications?.unread_count ?? 0,
);
const flashSuccess = computed(
    () => (page.props as any)?.flash?.success ?? null,
);
const flashError = computed(() => (page.props as any)?.flash?.error ?? null);
const logoutForm = useForm({});
const impersonationForm = useForm({});
const mainLinks = computed(() => [
    {
        href: '/dashboard',
        label: __('frontend.nav.dashboard'),
        title: __('frontend.layout.page_titles.dashboard'),
        icon: LayoutDashboard,
        visible: true,
    },
    {
        href: '/merchants',
        label: __('frontend.nav.merchants'),
        title: __('frontend.layout.page_titles.merchants'),
        icon: Store,
        visible: isApproved.value,
    },
    {
        href: '/invoices',
        label: __('frontend.nav.invoices'),
        title: __('frontend.layout.page_titles.invoices'),
        icon: FileText,
        visible: isApproved.value,
    },
    {
        href: '/addresses',
        label: __('frontend.nav.addresses'),
        title: __('frontend.layout.page_titles.addresses'),
        icon: Wallet,
        visible: isApproved.value,
    },
    {
        href: '/clients',
        label: __('frontend.nav.clients'),
        title: __('frontend.layout.page_titles.clients'),
        icon: Users,
        visible: isApproved.value,
    },
    {
        href: '/notifications',
        label: __('frontend.nav.notifications'),
        title: __('frontend.layout.page_titles.notifications'),
        icon: Bell,
        visible: isApproved.value,
    },
    {
        href: '/callback-logs',
        label: __('frontend.nav.callback_logs'),
        title: __('frontend.layout.page_titles.callback_logs'),
        icon: MessageSquare,
        visible: isApproved.value,
    },
    {
        href: '/api',
        label: __('frontend.nav.api_docs'),
        title: __('frontend.layout.page_titles.api_docs'),
        icon: Code2,
        visible: isApproved.value,
    },
]);
const adminLinks = computed(() => [
    {
        href: '/admin/users',
        label: __('frontend.nav.users'),
        title: __('frontend.layout.page_titles.users'),
        icon: Users,
    },
    {
        href: '/app-settings',
        label: __('frontend.nav.app_settings'),
        title: __('frontend.layout.page_titles.app_settings'),
        icon: Settings2,
    },
]);
function active(href: string) {
    return page.url.split('?')[0] === href || page.url.startsWith(href + '/');
}
const pageTitle = computed(
    () =>
        props.title ||
        [...mainLinks.value, ...adminLinks.value].find((item) =>
            active(item.href),
        )?.title ||
        '',
);
function logout() {
    logoutForm.post(logoutRoute().url, { preserveScroll: true });
}
function leaveImpersonation() {
    impersonationForm.post('/impersonate/leave', { preserveScroll: true });
}
</script>

<template>
    <div class="glass-app drawer min-h-dvh lg:drawer-open">
        <input
            id="app-drawer"
            v-model="drawerOpen"
            type="checkbox"
            class="drawer-toggle"
            tabindex="-1"
            aria-hidden="true"
        />
        <div class="drawer-content min-w-0" :inert="isMobileDrawerOpen">
            <header class="mobile-bar glass-panel lg:hidden">
                <button
                    ref="drawerTrigger"
                    type="button"
                    class="btn btn-square btn-ghost"
                    :aria-expanded="drawerOpen"
                    aria-controls="app-sidebar"
                    aria-label="Menu"
                    @click="drawerOpen = true"
                >
                    <Menu :size="21" />
                </button>
                <Link href="/dashboard" class="mobile-brand">{{
                    appName
                }}</Link>
                <ThemeSwitcher v-if="!isDesktop" />
            </header>
            <main class="app-main">
                <div v-if="pageTitle" class="app-topline">
                    <div class="min-w-0">
                        <p class="glass-kicker mb-2">
                            {{ appName }}
                            <span class="mx-1 opacity-40">/</span> WORKSPACE
                        </p>
                        <h1
                            v-if="!active('/dashboard')"
                            class="text-lg font-semibold tracking-tight sm:text-xl"
                        >
                            {{ pageTitle }}
                        </h1>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <slot name="header-actions" />
                        <Link
                            v-if="isApproved"
                            href="/notifications"
                            class="btn relative hidden btn-square btn-ghost sm:inline-flex"
                            :aria-label="__('frontend.nav.notifications')"
                            ><Bell :size="19" /><span
                                v-if="unreadNotifications > 0"
                                class="notification-dot"
                        /></Link>
                    </div>
                </div>
                <div v-if="flashSuccess || flashError" class="mb-5 grid gap-2">
                    <Alert
                        v-if="flashSuccess"
                        type="success"
                        :message="flashSuccess"
                    /><Alert
                        v-if="flashError"
                        type="error"
                        :message="flashError"
                    />
                </div>
                <slot />
                <footer class="app-footer glass-muted">
                    <span>USDT · TRON</span
                    ><span
                        >© {{ new Date().getFullYear() }} {{ appName }}</span
                    >
                </footer>
            </main>
        </div>
        <div class="drawer-side z-40 lg:z-20">
            <label
                for="app-drawer"
                aria-label="Close menu"
                class="drawer-overlay"
                @click.prevent="closeDrawer"
            ></label>
            <aside
                id="app-sidebar"
                ref="sidebar"
                class="app-sidebar glass-panel"
                :inert="!isDesktop && !drawerOpen"
                :role="isMobileDrawerOpen ? 'dialog' : undefined"
                :aria-modal="isMobileDrawerOpen ? true : undefined"
                aria-label="Main navigation"
            >
                <div class="sidebar-brand">
                    <Link
                        href="/dashboard"
                        class="flex min-w-0 items-center gap-3"
                        @click="closeDrawer"
                        ><span class="glass-brand-mark">₮</span
                        ><span
                            ><strong class="block text-sm tracking-[0.12em]">{{
                                appName
                            }}</strong
                            ><span
                                class="glass-muted text-[10px] tracking-[0.16em]"
                                >PAYMENT SPACE</span
                            ></span
                        ></Link
                    >
                    <button
                        ref="drawerClose"
                        type="button"
                        class="btn btn-square btn-ghost btn-sm lg:hidden"
                        aria-label="Close menu"
                        @click="closeDrawer"
                    >
                        <X :size="18" />
                    </button>
                </div>
                <div class="sidebar-divider" />
                <nav class="sidebar-nav" aria-label="Main navigation">
                    <p class="glass-kicker px-3 pb-3">WORKSPACE</p>
                    <ul class="space-y-1">
                        <template v-for="item in mainLinks" :key="item.href">
                            <li v-if="item.visible">
                                <Link
                                    :href="item.href"
                                    class="nav-link"
                                    :class="{
                                        'nav-current': active(item.href),
                                    }"
                                    :aria-current="
                                        active(item.href) ? 'page' : undefined
                                    "
                                    @click="closeDrawer"
                                    ><component
                                        :is="item.icon"
                                        :size="18"
                                        :stroke-width="1.6"
                                    /><span>{{ item.label }}</span
                                    ><span
                                        v-if="
                                            item.href === '/notifications' &&
                                            unreadNotifications > 0
                                        "
                                        class="nav-count"
                                        >{{ unreadNotifications }}</span
                                    ></Link
                                >
                            </li>
                        </template>
                    </ul>
                    <template v-if="isApproved && isAdmin">
                        <p class="glass-kicker mt-7 px-3 pb-3">
                            {{ __('frontend.nav.admin') }}
                        </p>
                        <ul class="space-y-1">
                            <li v-for="item in adminLinks" :key="item.href">
                                <Link
                                    :href="item.href"
                                    class="nav-link"
                                    :class="{
                                        'nav-current': active(item.href),
                                    }"
                                    :aria-current="
                                        active(item.href) ? 'page' : undefined
                                    "
                                    @click="closeDrawer"
                                    ><component
                                        :is="item.icon"
                                        :size="18"
                                        :stroke-width="1.6"
                                    /><span>{{ item.label }}</span></Link
                                >
                            </li>
                        </ul>
                    </template>
                </nav>
                <div
                    v-if="isImpersonated"
                    class="mx-3 mb-4 rounded-2xl border border-warning/40 bg-warning/10 p-3"
                >
                    <div class="text-sm font-semibold text-warning">
                        {{ __('frontend.nav.impersonation_title') }}
                    </div>
                    <p class="mb-2 text-xs opacity-80">
                        {{ __('frontend.nav.impersonation_description') }}
                    </p>
                    <button
                        class="btn w-full btn-sm btn-warning"
                        :disabled="impersonationForm.processing"
                        @click="leaveImpersonation"
                    >
                        {{ __('frontend.nav.impersonation_return') }}
                    </button>
                </div>
                <div class="sidebar-bottom">
                    <div class="mb-3 flex items-center gap-2">
                        <LanguageSwitcher /><ThemeSwitcher v-if="isDesktop" />
                    </div>
                    <Link
                        v-if="isApproved"
                        href="/settings/profile"
                        class="profile-glass glass-surface"
                        :aria-label="__('frontend.nav.profile')"
                        @click="closeDrawer"
                        ><span class="profile-avatar"
                            ><UserRound :size="18" /></span
                        ><span class="min-w-0 flex-1"
                            ><strong class="block truncate text-xs">{{
                                userName
                            }}</strong
                            ><span
                                class="glass-muted block truncate text-[10px]"
                                >{{ userEmail }}</span
                            ></span
                        ><ChevronsUpDown
                            :size="14"
                            class="glass-muted shrink-0"
                    /></Link>
                    <button
                        v-else
                        type="button"
                        class="btn w-full btn-ghost"
                        :disabled="logoutForm.processing"
                        @click="logout"
                    >
                        <LogOut :size="17" />{{ __('frontend.common.logout') }}
                    </button>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.app-main {
    width: 100%;
    max-width: 1600px;
    margin-inline: auto;
    padding: 34px 38px 12px;
}
.app-topline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
    min-height: 48px;
}
.app-sidebar {
    display: flex;
    flex-direction: column;
    width: 254px;
    height: calc(100dvh - 32px);
    margin: 16px 0 16px 16px;
    overflow-y: auto;
    overscroll-behavior: contain;
    background-color: color-mix(
        in srgb,
        var(--color-base-100),
        transparent 25%
    );
}
.sidebar-brand {
    padding: 25px 22px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.sidebar-divider {
    height: 1px;
    margin: 0 22px;
    background: linear-gradient(
        90deg,
        transparent,
        var(--glass-edge),
        transparent
    );
}
.sidebar-nav {
    flex: 1;
    padding: 22px 12px;
}
.nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 45px;
    padding: 11px 14px;
    border: 1px solid transparent;
    border-radius: 14px;
    font-size: 12px;
    font-weight: 500;
    color: var(--glass-muted);
    transition:
        background-color 160ms,
        color 160ms;
}
.nav-link svg {
    flex-shrink: 0;
}
.nav-link:hover {
    background: var(--glass-surface);
    color: var(--color-base-content);
}
.nav-current {
    color: var(--color-base-content);
    border-color: var(--glass-edge);
    background: linear-gradient(
        110deg,
        rgba(164, 216, 255, 0.18),
        rgba(164, 216, 255, 0.055)
    );
    box-shadow:
        inset 0 1px 0 var(--glass-highlight),
        0 4px 12px rgba(0, 0, 0, 0.04);
}
.nav-current svg {
    color: var(--color-primary);
}
.nav-count {
    margin-left: auto;
    border-radius: 6px;
    padding: 1px 5px;
    background: var(--glass-surface);
    color: var(--color-primary);
    font-size: 10px;
}
.sidebar-bottom {
    margin-top: auto;
    padding: 12px;
    border-top: 1px solid var(--glass-edge);
}
.profile-glass {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 10px;
    border-radius: 16px;
}
.profile-avatar {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    background: var(--glass-surface);
    border-radius: 50%;
    color: var(--color-primary);
}
.notification-dot {
    position: absolute;
    top: 9px;
    right: 9px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--color-primary);
}
.app-footer {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 30px 4px 10px;
    font-size: 10px;
    letter-spacing: 0.04em;
}
.mobile-bar {
    position: sticky;
    top: 12px;
    z-index: 25;
    align-items: center;
    justify-content: space-between;
    margin: 12px 14px;
    padding: 7px;
    border-radius: 22px;
}
.mobile-brand {
    font-size: 12px;
    font-weight: 650;
    letter-spacing: 0.12em;
}
@media (min-width: 1024px) {
    .drawer-side {
        overflow: visible;
    }
}
@media (max-width: 1023px) {
    .mobile-bar {
        display: flex;
    }
    .app-main {
        padding: 20px 18px 12px;
    }
    .app-sidebar {
        background-color: var(--color-base-100);
        margin: 12px;
        height: calc(100dvh - 24px);
        width: min(290px, calc(100vw - 52px));
    }
    .app-topline {
        margin-bottom: 22px;
    }
}
@media (max-width: 479px) {
    .app-main {
        padding-inline: 14px;
    }
    .app-topline :deep(.btn) {
        font-size: 12px;
        padding-inline: 12px;
    }
}
</style>
