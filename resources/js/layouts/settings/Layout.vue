<script setup lang="ts">
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor'; // временно скрыто
import { edit as editPassword } from '@/routes/user-password';
import { index as loginHistory } from '@/routes/login-history';
import { type NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ui/modal/ConfirmDialog.vue';
import { logout as logoutRoute } from '@/routes';
import { vueLang } from '@erag/lang-sync-inertia';

const { __ } = vueLang();

const sidebarNavItems = computed<NavItem[]>(() => [
    { title: __('frontend.settings.nav.profile'), href: editProfile() },
    { title: __('frontend.settings.nav.password'), href: editPassword() },
    { title: __('frontend.settings.nav.two_factor'), href: show() },
    { title: __('frontend.settings.nav.login_history'), href: loginHistory() },
]);

const page = usePage();
const currentPath = computed(() => page.url);

const isLogoutDialogOpen = ref(false);
const isLogoutProcessing = ref(false);

function requestLogout() {
    isLogoutDialogOpen.value = true;
}

function confirmLogout() {
    if (isLogoutProcessing.value) return;
    isLogoutProcessing.value = true;
    router.post(logoutRoute().url, {}, {
        onFinish: () => {
            isLogoutProcessing.value = false;
            isLogoutDialogOpen.value = false;
        },
    });
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-xl font-semibold flex items-center gap-2 py-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 opacity-60">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                {{ __('frontend.settings.title') }}
            </h1>
        </div>

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="btn btn-ghost justify-start w-full"
                        :class="urlIsActive(item.href, currentPath) ? 'btn-active' : ''"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
                <div class="divider my-4"></div>
                <button type="button" class="btn btn-error w-full" @click="requestLogout">
                    {{ __('frontend.common.logout') }}
                </button>
                <ConfirmDialog
                    v-model="isLogoutDialogOpen"
                    :loading="isLogoutProcessing"
                    :title="__('frontend.settings.logout.title')"
                    :message="__('frontend.settings.logout.message')"
                    :confirm-text="__('frontend.settings.logout.confirm')"
                    :cancel-text="__('frontend.common.cancel')"
                    :danger="true"
                    @confirm="confirmLogout"
                />
            </aside>

            <div class="divider my-6 lg:hidden"></div>

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <div class="rounded-box bg-base-100 shadow-sm p-4 lg:p-6">
                        <slot />
                    </div>
                </section>
            </div>
        </div>
    </div>

</template>
