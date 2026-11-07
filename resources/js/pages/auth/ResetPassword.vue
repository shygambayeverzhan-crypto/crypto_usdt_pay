<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';
import { Head, useForm } from '@inertiajs/vue3';
import { vueLang } from '@erag/lang-sync-inertia';

const props = defineProps<{
    token: string;
    email: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});
const { __ } = vueLang();

function submit() {
    form.post(update.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthLayout
        :title="__('frontend.auth.reset.title')"
        :description="__('frontend.auth.reset.description')"
    >
        <Head :title="__('frontend.auth.reset.page_title')" />

        <form @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <label for="email" class="label">
                        <span class="label-text">{{ __('frontend.auth.reset.email') }}</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="form.email"
                        class="input input-bordered w-full"
                        readonly
                    />
                    <p v-if="form.errors.email" class="text-error text-sm">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="password" class="label">
                        <span class="label-text">{{ __('frontend.auth.reset.password') }}</span>
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="input input-bordered w-full"
                        autofocus
                        :placeholder="__('frontend.auth.reset.password')"
                        v-model="form.password"
                    />
                    <p v-if="form.errors.password" class="text-error text-sm">{{ form.errors.password }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="password_confirmation" class="label">
                        <span class="label-text">{{ __('frontend.auth.reset.password_confirmation') }}</span>
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="input input-bordered w-full"
                        :placeholder="__('frontend.auth.reset.password_confirmation')"
                        v-model="form.password_confirmation"
                    />
                    <p v-if="form.errors.password_confirmation" class="text-error text-sm">{{ form.errors.password_confirmation }}</p>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary mt-2 w-full"
                    :disabled="form.processing"
                    data-test="reset-password-button"
                >
                    <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                    {{ __('frontend.auth.reset.submit') }}
                </button>
            </div>
        </form>
    </AuthLayout>
</template>


