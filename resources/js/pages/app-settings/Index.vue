<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BreadcrumbItem, LocaleOption } from '@/types';
import FormControl from '@/components/form/FormControl.vue';
import Label from '@/components/form/Label.vue';
import Input from '@/components/form/Input.vue';
import FlagIcon from '@/components/ui/FlagIcon.vue';
import { vueLang } from '@erag/lang-sync-inertia';

interface SettingItem {
    id?: number;
    currency: string;
    min_invoice_amount: number;
    max_invoice_amount: number;
}

interface PageProps {
    settings: SettingItem[];
    currencies: string[];
    locales: LocaleOption[];
    enabled_locales: string[];
}

const props = defineProps<PageProps>();
const { __ } = vueLang();

const form = useForm({
    settings: props.currencies.map((c) => {
        const found = props.settings.find((s) => s.currency === c);
        return {
            currency: c,
            min_invoice_amount: found?.min_invoice_amount ?? 0,
            max_invoice_amount: found?.max_invoice_amount ?? 0,
        };
    }),
});

const submit = () => {
    form.put('/app-settings');
};

const localeCodes = computed(() => props.locales.map((locale) => locale.code));

const localesForm = useForm({
    locales: props.enabled_locales.length ? [...props.enabled_locales] : localeCodes.value,
});

const isLocaleEnabled = (code: string) => localesForm.locales.includes(code);
const toggleLocale = (code: string) => {
    if (isLocaleEnabled(code)) {
        localesForm.locales = localesForm.locales.filter((item) => item !== code);
        return;
    }

    localesForm.locales = [...localesForm.locales, code];
};

const resetLocales = () => {
    localesForm.locales = [...localeCodes.value];
};

const isAllSelected = computed(() => localesForm.locales.length === localeCodes.value.length);

const submitLocales = () => {
    localesForm.put('/app-settings/locales');
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: __('frontend.app_settings_page.breadcrumb.home'), href: '/dashboard' },
    { title: __('frontend.app_settings_page.breadcrumb.title') },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="(item, idx) in form.settings" :key="item.currency" class="card bg-base-100 shadow">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <h2 class="card-title">{{ item.currency }}</h2>
                            </div>

                            <FormControl>
                                <Label :for="`min-${item.currency}`">{{ __('frontend.app_settings_page.min_amount', { currency: item.currency }) }}</Label>
                                <Input
                                    :id="`min-${item.currency}`"
                                    v-model="form.settings[idx].min_invoice_amount"
                                    type="number"
                                    step="0.000001"
                                    min="0"
                                />
                            </FormControl>

                            <FormControl class="mt-4">
                                <Label :for="`max-${item.currency}`">{{ __('frontend.app_settings_page.max_amount', { currency: item.currency }) }}</Label>
                                <Input
                                    :id="`max-${item.currency}`"
                                    v-model="form.settings[idx].max_invoice_amount"
                                    type="number"
                                    step="0.000001"
                                    min="0"
                                />
                            </FormControl>
                        </div>
                    </div>
                </div>

                <div class="flex justify-start">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        {{ __('frontend.app_settings_page.save') }}
                    </button>
                </div>
            </form>

            <form @submit.prevent="submitLocales">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="card bg-base-100 shadow">
                        <div class="card-body space-y-4">
                            <div class="grid gap-2">
                                <div>
                                    <h2 class="card-title">{{ __('frontend.app_settings_page.languages.title') }}</h2>
                                    <p class="text-sm opacity-70">
                                        {{ __('frontend.app_settings_page.languages.subtitle') }}
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="table table-sm">
                                    <thead>
                                    <tr>
                                        <th>{{ __('frontend.app_settings_page.languages.language') }}</th>
                                        <th class="text-right">{{ __('frontend.app_settings_page.languages.enabled') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="locale in props.locales" :key="locale.code">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <FlagIcon :code="locale.flag" size="M" />
                                                <div>
                                                    <div class="font-semibold">{{ locale.label }}</div>
                                                    <div class="text-xs uppercase opacity-60">{{ locale.code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <input
                                                type="checkbox"
                                                class="toggle toggle-primary"
                                                :checked="isLocaleEnabled(locale.code)"
                                                :disabled="localesForm.processing"
                                                @change="toggleLocale(locale.code)"
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-between">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm"
                                        :disabled="localesForm.processing || isAllSelected"
                                        @click="resetLocales"
                                    >
                                        {{ __('frontend.app_settings_page.languages.select_all') }}
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary" :disabled="localesForm.processing">
                                    <span v-if="localesForm.processing" class="loading loading-spinner loading-xs mr-2" />
                                    {{ __('frontend.app_settings_page.languages.save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>

</template>


