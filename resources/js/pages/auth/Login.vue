<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store as loginStore } from '@/routes/login';
import { request as forgotRequest } from '@/routes/password';
import { vueLang } from '@erag/lang-sync-inertia';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const { __ } = vueLang();

function submit() {
    form.post(loginStore.url(), {
        onError: () => document.getElementById('email')?.focus(),
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthBase
        :title="__('frontend.auth.login.title')"
        :description="__('frontend.auth.login.description')"
    >
        <Head :title="__('frontend.auth.login.page_title')" />

        <div
            v-if="props.status"
            role="status"
            class="mb-5 rounded-2xl border border-success/20 bg-success/10 p-3 text-sm text-success"
        >
            {{ props.status }}
        </div>

        <form @submit.prevent="submit" class="auth-form flex flex-col gap-6">
            <div class="auth-fields grid gap-6">
                <div class="grid gap-2">
                    <label for="email" class="label">
                        <span class="label-text">{{
                            __('frontend.auth.login.email')
                        }}</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="email"
                        :placeholder="
                            __('frontend.auth.login.email_placeholder')
                        "
                        v-model="form.email"
                        class="input-bordered input w-full"
                    />
                    <p
                        v-if="form.errors.email"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <label for="password" class="label">
                            <span class="label-text">{{
                                __('frontend.auth.login.password')
                            }}</span>
                        </label>
                        <Link
                            v-if="props.canResetPassword"
                            :href="forgotRequest().url"
                            class="auth-inline-link link link-hover"
                            tabindex="5"
                        >
                            {{ __('frontend.auth.login.forgot') }}
                        </Link>
                    </div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        tabindex="2"
                        autocomplete="current-password"
                        :placeholder="__('frontend.auth.login.password')"
                        v-model="form.password"
                        class="input-bordered input w-full"
                    />
                    <p
                        v-if="form.errors.password"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <label
                        for="remember"
                        class="auth-remember label cursor-pointer gap-3"
                    >
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            tabindex="3"
                            class="checkbox"
                            v-model="form.remember"
                        />
                        <span class="label-text">{{
                            __('frontend.auth.login.remember')
                        }}</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="auth-submit btn w-full btn-primary"
                    tabindex="4"
                    :disabled="form.processing"
                    data-test="login-button"
                >
                    <span
                        v-if="form.processing"
                        class="loading mr-2 loading-sm loading-spinner"
                    />
                    {{ __('frontend.auth.login.submit') }}
                    <ArrowRight
                        v-if="!form.processing"
                        :size="17"
                        :stroke-width="1.7"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <div
                class="grid gap-4 text-center text-sm text-base-content/60"
                v-if="props.canRegister"
            >
                <span class="auth-divider">{{
                    __('frontend.auth.login.no_account')
                }}</span>
                <Link
                    :href="register().url"
                    tabindex="6"
                    class="auth-secondary btn w-full btn-outline"
                    data-test="registration-link"
                    >{{ __('frontend.auth.login.register') }}</Link
                >
            </div>
        </form>
    </AuthBase>
</template>
