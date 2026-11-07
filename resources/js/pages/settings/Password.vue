<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit, update as updatePassword } from '@/routes/user-password';
import { Head, useForm } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const { __ } = vueLang();

function submit() {
    form.post(updatePassword.url(), {
        preserveScroll: true,
        onBefore: () => form.transform((data) => ({ ...data, _method: 'PUT' })),
        onFinish: () => form.reset('password', 'password_confirmation', 'current_password'),
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: __('frontend.password_page.breadcrumb'), href: edit().url },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="__('frontend.password_page.breadcrumb')" />

        <SettingsLayout>
            <div class="space-y-6">
                <div class="space-y-1">
                    <h2 class="text-lg font-medium">{{ __('frontend.password_page.title') }}</h2>
                    <p class="text-sm text-base-content/70">{{ __('frontend.password_page.subtitle') }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <label for="current_password" class="label"><span class="label-text">{{ __('frontend.password_page.current_password') }}</span></label>
                        <input id="current_password" name="current_password" type="password" class="input input-bordered w-full" autocomplete="current-password" :placeholder="__('frontend.password_page.current_password')" v-model="form.current_password" />
                        <p v-if="form.errors.current_password" class="text-error text-sm">{{ form.errors.current_password }}</p>
                    </div>

                    <div class="grid gap-2">
                        <label for="password" class="label"><span class="label-text">{{ __('frontend.password_page.password') }}</span></label>
                        <input id="password" name="password" type="password" class="input input-bordered w-full" autocomplete="new-password" :placeholder="__('frontend.password_page.password')" v-model="form.password" />
                        <p v-if="form.errors.password" class="text-error text-sm">{{ form.errors.password }}</p>
                    </div>

                    <div class="grid gap-2">
                        <label for="password_confirmation" class="label"><span class="label-text">{{ __('frontend.password_page.password_confirmation') }}</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input input-bordered w-full" autocomplete="new-password" :placeholder="__('frontend.password_page.password_confirmation')" v-model="form.password_confirmation" />
                        <p v-if="form.errors.password_confirmation" class="text-error text-sm">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing" data-test="update-password-button">
                            <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                            {{ __('frontend.password_page.save') }}
                        </button>
                        <p v-if="form.recentlySuccessful" class="text-sm text-base-content/70">{{ __('frontend.password_page.saved') }}</p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>


