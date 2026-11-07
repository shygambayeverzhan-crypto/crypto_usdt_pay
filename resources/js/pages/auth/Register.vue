<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store as registerStore } from '@/routes/register';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { vueLang } from '@erag/lang-sync-inertia';

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

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <label for="name" class="label">
                        <span class="label-text">{{ __('frontend.auth.register.name') }}</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="name"
                        name="name"
                        :placeholder="__('frontend.auth.register.name_placeholder')"
                        v-model="form.name"
                        class="input input-bordered w-full"
                    />
                    <p v-if="form.errors.name" class="text-error text-sm">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="email" class="label">
                        <span class="label-text">{{ __('frontend.auth.register.email') }}</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        required
                        tabindex="2"
                        autocomplete="email"
                        name="email"
                        :placeholder="__('frontend.auth.register.email_placeholder')"
                        v-model="form.email"
                        class="input input-bordered w-full"
                    />
                    <p v-if="form.errors.email" class="text-error text-sm">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="password" class="label">
                        <span class="label-text">{{ __('frontend.auth.register.password') }}</span>
                    </label>
                    <input
                        id="password"
                        type="password"
                        required
                        tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="__('frontend.auth.register.password_placeholder')"
                        v-model="form.password"
                        class="input input-bordered w-full"
                    />
                    <p v-if="form.errors.password" class="text-error text-sm">{{ form.errors.password }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="password_confirmation" class="label">
                        <span class="label-text">{{ __('frontend.auth.register.password_confirmation') }}</span>
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="__('frontend.auth.register.password_confirm_placeholder')"
                        v-model="form.password_confirmation"
                        class="input input-bordered w-full"
                    />
                    <p v-if="form.errors.password_confirmation" class="text-error text-sm">{{ form.errors.password_confirmation }}</p>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary mt-2 w-full"
                    tabindex="5"
                    :disabled="form.processing"
                    data-test="register-user-button"
                >
                    <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                    {{ __('frontend.auth.register.submit') }}
                </button>
            </div>

            <div class="text-center text-sm text-base-content/60">
                {{ __('frontend.auth.register.have_account') }}
                <Link :href="login().url" class="link link-hover" tabindex="6">{{ __('frontend.auth.register.login') }}</Link>
            </div>
        </form>
    </AuthBase>
</template>


