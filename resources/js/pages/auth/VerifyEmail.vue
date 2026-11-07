<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { vueLang } from '@erag/lang-sync-inertia';

const props = defineProps<{ status?: string }>();

const form = useForm({});
const { __ } = vueLang();

function resend() {
    form.post(send.url());
}
</script>

<template>
    <AuthLayout
        :title="__('frontend.auth.verify_email.title')"
        :description="__('frontend.auth.verify_email.description')"
    >
        <Head :title="__('frontend.auth.verify_email.page_title')" />

        <div
            v-if="props.status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-success"
        >
            {{ __('frontend.auth.verify_email.link_sent') }}
        </div>

        <div class="space-y-6 text-center">
            <button class="btn btn-secondary" :disabled="form.processing" @click="resend">
                <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                {{ __('frontend.auth.verify_email.resend') }}
            </button>

            <Link :href="logout().url" as="button" class="link link-hover block text-sm mx-auto">
                {{ __('frontend.auth.verify_email.logout') }}
            </Link>
        </div>
    </AuthLayout>
</template>


