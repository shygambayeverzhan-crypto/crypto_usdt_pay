<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/two-factor/login';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface AuthConfigContent {
    title: string;
    description: string;
    toggleText: string;
}

const showRecoveryInput = ref(false);
const { __ } = vueLang();

const authConfigContent = computed<AuthConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: __('frontend.auth.two_factor_challenge.recovery_title'),
            description: __('frontend.auth.two_factor_challenge.recovery_description'),
            toggleText: __('frontend.auth.two_factor_challenge.toggle_to_code'),
        };
    }

    return {
        title: __('frontend.auth.two_factor_challenge.auth_code_title'),
        description: __('frontend.auth.two_factor_challenge.auth_code_description'),
        toggleText: __('frontend.auth.two_factor_challenge.toggle_to_recovery'),
    };
});

const form = useForm({
    code: '',
    recovery_code: '',
});

function submit() {
    const payload = showRecoveryInput.value
        ? { recovery_code: form.recovery_code }
        : { code: form.code };

    form.post(store.url(), {
        onError: () => {
            form.code = '';
        },
        preserveScroll: true,
        data: payload as any,
    });
}

function toggleRecoveryMode() {
    showRecoveryInput.value = !showRecoveryInput.value;
    form.clearErrors();
    form.code = '';
    form.recovery_code = '';
}
</script>

<template>
    <AuthLayout :title="authConfigContent.title" :description="authConfigContent.description">
        <Head :title="__('frontend.auth.two_factor_challenge.auth_code_title')" />

        <div class="space-y-6">
            <template v-if="!showRecoveryInput">
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="flex flex-col items-center justify-center space-y-3 text-center">
                        <div class="w-full">
                            <input
                            id="otp"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            name="code"
                            :placeholder="__('frontend.auth.two_factor_challenge.code_placeholder')"
                            class="input input-bordered w-full tracking-widest text-center"
                            v-model="form.code"
                            autofocus
                            />
                        </div>
                        <p v-if="form.errors.code" class="text-error text-sm">{{ form.errors.code }}</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                        {{ __('frontend.auth.two_factor_challenge.submit') }}
                    </button>
                    <div class="text-center text-sm text-base-content/60">
                        <span>{{ __('frontend.auth.two_factor_challenge.or') }} </span>
                        <button type="button" class="link link-hover" @click="toggleRecoveryMode">
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </form>
            </template>

            <template v-else>
                <form @submit.prevent="submit" class="space-y-4">
                    <input
                        name="recovery_code"
                        type="text"
                        :placeholder="__('frontend.auth.two_factor_challenge.recovery_placeholder')"
                        :autofocus="showRecoveryInput"
                        required
                        v-model="form.recovery_code"
                        class="input input-bordered w-full"
                    />
                    <p v-if="form.errors.recovery_code" class="text-error text-sm">{{ form.errors.recovery_code }}</p>
                    <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm mr-2" />
                        {{ __('frontend.auth.two_factor_challenge.submit') }}
                    </button>

                    <div class="text-center text-sm text-base-content/60">
                        <span>{{ __('frontend.auth.two_factor_challenge.or') }} </span>
                        <button type="button" class="link link-hover" @click="toggleRecoveryMode">
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </AuthLayout>
</template>


