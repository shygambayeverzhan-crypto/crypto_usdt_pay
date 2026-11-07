<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit, update as updateProfile } from '@/routes/profile';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user as { name: string; email: string; email_verified_at?: string | null };
const { __ } = vueLang();

const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
});

function submit() {
    form.post(updateProfile.url(), {
        preserveScroll: true,
        onBefore: () => form.transform((data) => ({ ...data, _method: 'PATCH' })),
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: __('frontend.profile.breadcrumb'), href: edit().url },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="__('frontend.profile.breadcrumb')" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="space-y-1">
                    <h2 class="text-lg font-medium">{{ __('frontend.profile.title') }}</h2>
                    <p class="text-sm text-base-content/70">{{ __('frontend.profile.subtitle') }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <label for="name" class="label"><span class="label-text">{{ __('frontend.profile.name') }}</span></label>
                        <input id="name" name="name" type="text" class="input input-bordered w-full" v-model="form.name" :placeholder="__('frontend.profile.name_placeholder')" />
                        <p v-if="form.errors.name" class="text-error text-sm">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid gap-2">
                        <label for="email" class="label"><span class="label-text">{{ __('frontend.profile.email') }}</span></label>
                        <input id="email" name="email" type="email" class="input input-bordered w-full" v-model="form.email" placeholder="email@example.com" />
                        <p v-if="form.errors.email" class="text-error text-sm">{{ form.errors.email }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing" data-test="update-profile-button">
                            <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                            {{ __('frontend.profile.save') }}
                        </button>
                        <p v-if="form.recentlySuccessful" class="text-sm text-base-content/70">{{ __('frontend.profile.saved') }}</p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>


