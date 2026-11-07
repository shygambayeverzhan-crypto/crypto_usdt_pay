<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email as sendEmail } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { vueLang } from '@erag/lang-sync-inertia';

const props = defineProps<{ status?: string }>();

const form = useForm({
    email: '',
});
const { __ } = vueLang();

function submit() {
    form.post(sendEmail.url());
}
</script>

<template>
    <AuthLayout
        :title="__('frontend.auth.forgot.title')"
        :description="__('frontend.auth.forgot.description')"
    >
        <Head :title="__('frontend.auth.forgot.page_title')" />

        <div v-if="props.status" class="mb-4 text-center text-sm font-medium text-success">
            {{ props.status }}
        </div>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-2">
                    <label for="email" class="label">
                        <span class="label-text">{{ __('frontend.auth.forgot.email') }}</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        :placeholder="__('frontend.auth.forgot.email_placeholder')"
                        v-model="form.email"
                        class="input input-bordered w-full"
                    />
                    <p v-if="form.errors.email" class="text-error text-sm">{{ form.errors.email }}</p>
                </div>

                <div class="my-2">
                    <button class="btn btn-primary w-full" :disabled="form.processing" data-test="email-password-reset-link-button">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                        {{ __('frontend.auth.forgot.submit') }}
                    </button>
                </div>
            </form>

            <div class="space-x-1 text-center text-sm text-base-content/60">
                <span>{{ __('frontend.auth.forgot.back_to_login') }}</span>
                <Link :href="login().url" class="link link-hover">{{ __('frontend.auth.forgot.login') }}</Link>
            </div>
        </div>
    </AuthLayout>
</template>


