<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store as registerStore } from '@/routes/register';
import { vueLang } from '@erag/lang-sync-inertia';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const { __ } = vueLang();

function submit() {
    form.post(registerStore.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthBase
        :title="__('frontend.auth.register.title')"
        :description="__('frontend.auth.register.description')"
    >
        <Head :title="__('frontend.auth.register.page_title')" />

        <form @submit.prevent="submit" class="auth-form flex flex-col gap-6">
            <div class="auth-fields auth-fields-register grid gap-6">
                <div class="grid gap-2">
                    <label for="name" class="label">
                        <span class="label-text">{{
                            __('frontend.auth.register.name')
                        }}</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="name"
                        name="name"
                        :placeholder="
                            __('frontend.auth.register.name_placeholder')
                        "
                        v-model="form.name"
                        class="input-bordered input w-full"
                    />
                    <p
                        v-if="form.errors.name"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <label for="email" class="label">
                        <span class="label-text">{{
                            __('frontend.auth.register.email')
                        }}</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        required
                        tabindex="2"
                        autocomplete="email"
                        name="email"
                        :placeholder="
                            __('frontend.auth.register.email_placeholder')
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
                    <label for="password" class="label">
                        <span class="label-text">{{
                            __('frontend.auth.register.password')
                        }}</span>
                    </label>
                    <input
                        id="password"
                        type="password"
                        required
                        tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="
                            __('frontend.auth.register.password_placeholder')
                        "
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

                <div class="grid gap-2">
                    <label for="password_confirmation" class="label">
                        <span class="label-text">{{
                            __('frontend.auth.register.password_confirmation')
                        }}</span>
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="
                            __(
                                'frontend.auth.register.password_confirm_placeholder',
                            )
                        "
                        v-model="form.password_confirmation"
                        class="input-bordered input w-full"
                    />
                    <p
                        v-if="form.errors.password_confirmation"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="auth-submit btn w-full btn-primary"
                    tabindex="5"
                    :disabled="form.processing"
                    data-test="register-user-button"
                >
                    <span
                        v-if="form.processing"
                        class="loading mr-2 loading-sm loading-spinner"
                    />
                    {{ __('frontend.auth.register.submit') }}
                    <ArrowRight
                        v-if="!form.processing"
                        :size="17"
                        :stroke-width="1.7"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <div class="grid gap-4 text-center text-sm text-base-content/60">
                <span class="auth-divider">{{
                    __('frontend.auth.register.have_account')
                }}</span>
                <Link
                    :href="login().url"
                    class="auth-secondary btn w-full btn-outline"
                    tabindex="6"
                    >{{ __('frontend.auth.register.login') }}</Link
                >
            </div>
        </form>
    </AuthBase>
</template>
