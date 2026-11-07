<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm, disable, enable, show } from '@/routes/two-factor';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    hasUnconfirmedTwoFactor?: boolean;
    status?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
    hasUnconfirmedTwoFactor: false,
    status: null,
});

const { __ } = vueLang();

const breadcrumbs: BreadcrumbItem[] = [
    { title: __('frontend.two_factor.breadcrumb'), href: show.url() },
];

const enableForm = useForm({});
const disableForm = useForm({ _method: 'DELETE' });
const confirmForm = useForm({ code: '' });
const jsonHeaders = { Accept: 'application/json' };

const {
    qrCodeSvg,
    manualSetupKey,
    recoveryCodesList,
    errors,
    hasSetupData,
    clearTwoFactorAuthData,
    fetchSetupData,
    fetchRecoveryCodes,
} = useTwoFactorAuth();

const hasPendingConfirmation = computed<boolean>(
    () => props.requiresConfirmation && props.hasUnconfirmedTwoFactor,
);

const showSetupBlock = computed<boolean>(
    () => props.twoFactorEnabled || hasPendingConfirmation.value,
);

async function loadTwoFactorData() {
    if (!showSetupBlock.value) {
        clearTwoFactorAuthData();
        return;
    }

    await fetchSetupData();
    await fetchRecoveryCodes();
}

function submitEnable() {
    enableForm.post(enable.url(), {
        preserveScroll: true,
        headers: jsonHeaders,
        onSuccess: () => {
            router.reload({
                only: ['twoFactorEnabled', 'hasUnconfirmedTwoFactor', 'status'],
                onSuccess: loadTwoFactorData,
            });
        },
    });
}

function submitDisable() {
    disableForm.post(disable.url(), {
        preserveScroll: true,
        headers: jsonHeaders,
        onSuccess: () => {
            clearTwoFactorAuthData();
        },
    });
}

function submitConfirm() {
    confirmForm.post(confirm.url(), {
        preserveScroll: true,
        headers: jsonHeaders,
        onSuccess: () => {
            confirmForm.reset('code');
            fetchRecoveryCodes();
        },
    });
}

watch(
    () => [props.twoFactorEnabled, props.hasUnconfirmedTwoFactor],
    () => {
        loadTwoFactorData();
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="__('frontend.two_factor.title')" />
        <SettingsLayout>
            <div class="space-y-6">
                <div class="space-y-1">
                    <h2 class="text-lg font-medium">{{ __('frontend.two_factor.title') }}</h2>
                    <p class="text-sm text-base-content/70">{{ __('frontend.two_factor.subtitle') }}</p>
                </div>

                <div class="space-y-4">
                    <div v-if="twoFactorEnabled" class="flex flex-col items-start space-y-4">
                        <div class="badge badge-success">{{ __('frontend.two_factor.enabled.badge') }}</div>
                        <p class="text-base-content/70">
                            {{ __('frontend.two_factor.enabled.description') }}
                        </p>
                        <button class="btn btn-error" :disabled="disableForm.processing" @click="submitDisable">
                            <span v-if="disableForm.processing" class="loading loading-spinner loading-sm mr-2" />
                            {{ __('frontend.two_factor.enabled.disable') }}
                        </button>
                    </div>

                    <div v-else-if="hasPendingConfirmation" class="flex flex-col items-start space-y-3">
                        <div class="badge badge-warning text-warning-content">{{ __('frontend.two_factor.pending.badge') }}</div>
                        <p class="text-base-content/70">
                            {{ __('frontend.two_factor.pending.description') }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <button class="btn btn-error" :disabled="disableForm.processing" @click="submitDisable">
                                <span v-if="disableForm.processing" class="loading loading-spinner loading-sm mr-2" />
                                {{ __('frontend.two_factor.pending.cancel') }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-start space-y-4">
                        <div class="badge badge-error">{{ __('frontend.two_factor.disabled.badge') }}</div>
                        <p class="text-base-content/70">
                            {{ __('frontend.two_factor.disabled.description') }}
                        </p>
                        <button class="btn btn-primary" :disabled="enableForm.processing" @click="submitEnable">
                            <span v-if="enableForm.processing" class="loading loading-spinner loading-sm mr-2" />
                            {{ __('frontend.two_factor.disabled.enable') }}
                        </button>
                    </div>
                </div>

                <div v-if="showSetupBlock" class="card border bg-base-100">
                    <div class="card-body space-y-6">
                        <div v-if="errors.length" class="alert alert-error">
                            <span>{{ __('frontend.two_factor.errors.load_failed') }}</span>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <h3 class="font-medium">{{ __('frontend.two_factor.qr.title') }}</h3>
                                <div v-if="qrCodeSvg" class="rounded-lg bg-base-200 p-4 flex justify-center" v-html="qrCodeSvg" />
                                <p v-else class="text-sm text-base-content/70">{{ __('frontend.two_factor.qr.placeholder') }}</p>
                            </div>

                            <div class="space-y-2">
                                <h3 class="font-medium">{{ __('frontend.two_factor.manual.title') }}</h3>
                                <div
                                    v-if="manualSetupKey"
                                    class="rounded-lg bg-base-200 p-3 font-mono text-sm sm:text-lg tracking-widest"
                                >
                                    {{ manualSetupKey }}
                                </div>
                                <p v-else class="text-sm text-base-content/70">{{ __('frontend.two_factor.manual.placeholder') }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium">{{ __('frontend.two_factor.recovery.title') }}</h3>
                            </div>
                            <div
                                v-if="recoveryCodesList.length"
                                class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                            >
                                <div
                                    v-for="code in recoveryCodesList"
                                    :key="code"
                                    class="rounded-lg border border-base-200 bg-base-200 px-3 py-2 font-mono text-sm"
                                >
                                    {{ code }}
                                </div>
                            </div>
                            <p v-else class="text-sm text-base-content/70">
                                {{ __('frontend.two_factor.recovery.placeholder') }}
                            </p>
                        </div>

                        <form v-if="hasPendingConfirmation && hasSetupData" class="space-y-3" @submit.prevent="submitConfirm">
                            <div class="space-y-1 grid">
                                <label for="confirm-code" class="text-sm font-medium">{{ __('frontend.two_factor.confirm.label') }}</label>
                                <input
                                    id="confirm-code"
                                    name="code"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    maxlength="6"
                                    class="input input-bordered w-full md:w-64"
                                    v-model="confirmForm.code"
                                    :placeholder="__('frontend.two_factor.confirm.placeholder')"
                                    required
                                />
                                <p v-if="confirmForm.errors.code" class="text-sm text-error">
                                    {{ confirmForm.errors.code }}
                                </p>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="confirmForm.processing">
                                <span v-if="confirmForm.processing" class="loading loading-spinner loading-sm mr-2" />
                                {{ __('frontend.two_factor.confirm.submit') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
