<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Head, useForm } from '@inertiajs/vue3';
import { vueLang } from '@erag/lang-sync-inertia';

const form = useForm({
    password: '',
});
const { __ } = vueLang();

function submit() {
    form.post(store.url(), {
        onFinish: () => form.reset('password'),
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthLayout
        :title="__('frontend.auth.confirm_password.title')"
        :description="__('frontend.auth.confirm_password.description')"
    >
        <Head :title="__('frontend.auth.confirm_password.page_title')" />

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-2">
                <label for="password" class="label">
                    <span class="label-text">{{ __('frontend.auth.confirm_password.password') }}</span>
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="input input-bordered w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                    v-model="form.password"
                />
                <p v-if="form.errors.password" class="text-error text-sm">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center">
                <button class="btn btn-primary w-full" :disabled="form.processing" data-test="confirm-password-button">
                    <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                    {{ __('frontend.auth.confirm_password.submit') }}
                </button>
            </div>
        </form>
    </AuthLayout>
</template>


