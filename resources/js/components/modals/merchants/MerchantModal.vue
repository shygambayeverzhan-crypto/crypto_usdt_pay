<script setup lang="ts">
import { computed } from 'vue';
import ModalDialog from '@/components/ui/modal/ModalDialog.vue';
import FormControl from '@/components/form/FormControl.vue';
import Label from '@/components/form/Label.vue';
import Input from '@/components/form/Input.vue';
import Textarea from '@/components/form/Textarea.vue';
import FileInput from '@/components/form/FileInput.vue';
import { vueLang } from '@erag/lang-sync-inertia';

export interface MerchantForm {
    name: string;
    description: string;
    initials: string;
    back_url: string;
    white_label_enabled: boolean;
    invoice_expires_in_minutes: number;
    logo: File | null;
}

type MerchantFormErrors = Partial<Record<keyof MerchantForm, string>>;

interface Props {
    modelValue: boolean;
    form: MerchantForm;
    errors: MerchantFormErrors | null;
    loading: boolean;
    mode?: 'create' | 'edit';
    currentLogoUrl?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    loading: false,
    mode: 'create',
    currentLogoUrl: null,
    form: () => ({
        name: '',
        description: '',
        initials: '',
        back_url: '',
        white_label_enabled: true,
        invoice_expires_in_minutes: 30,
        logo: null,
    }),
    errors: () => ({} as MerchantFormErrors),
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'update:form', value: MerchantForm): void;
    (e: 'submit'): void;
    (e: 'close'): void;
}>();

const form = computed({
    get: () => props.form,
    set: (value: MerchantForm) => emit('update:form', value),
});

const { __ } = vueLang();
const fieldErrors = computed<MerchantFormErrors>(() => props.errors ?? {});

const modalTitle = computed(() =>
    props.mode === 'edit'
        ? __('frontend.merchants.modals.edit.title')
        : __('frontend.merchants.modals.create.title')
);
const modalDescription = computed(() =>
    props.mode === 'edit'
        ? __('frontend.merchants.modals.edit.description')
        : __('frontend.merchants.modals.create.description')
);

function close() {
    emit('update:modelValue', false);
    emit('close');
}

function submit() {
    emit('submit');
}
</script>

<template>
    <ModalDialog
        :model-value="modelValue"
        :title="modalTitle"
        :description="modalDescription"
        size="xl"
        placement="bottom"
        @update:modelValue="emit('update:modelValue', $event)"
        @close="close"
    >
        <form class="mt-4 grid gap-4" @submit.prevent="submit">
            <FormControl :error="fieldErrors.name">
                <Label for="merchant-name" required>{{ __('frontend.merchants.fields.name') }}</Label>
                <Input
                    id="merchant-name"
                    v-model="form.name"
                    type="text"
                    :placeholder="__('frontend.merchants.fields.name_placeholder')"
                    required
                />
            </FormControl>

            <FormControl :error="fieldErrors.initials">
                <div class="flex items-center justify-between gap-2">
                    <Label for="merchant-initials" required>{{ __('frontend.merchants.fields.initials') }}</Label>
                    <span class="text-xs text-base-content/70">{{ __('frontend.merchants.fields.initials_hint') }}</span>
                </div>
                <Input
                    id="merchant-initials"
                    v-model="form.initials"
                    type="text"
                    maxlength="16"
                    :placeholder="__('frontend.merchants.fields.initials_placeholder')"
                    required
                />
            </FormControl>

            <FormControl :error="fieldErrors.description">
                <Label for="merchant-description">{{ __('frontend.merchants.fields.description') }}</Label>
                <Textarea
                    id="merchant-description"
                    v-model="form.description"
                    rows="3"
                    :placeholder="__('frontend.merchants.fields.description_placeholder')"
                />
            </FormControl>

            <FormControl :error="fieldErrors.back_url">
                <div class="flex items-center justify-between gap-2">
                    <Label for="merchant-back-url">{{ __('frontend.merchants.fields.back_url') }}</Label>
                </div>
                <Input
                    id="merchant-back-url"
                    v-model="form.back_url"
                    type="url"
                    :placeholder="__('frontend.merchants.fields.back_url_placeholder')"
                />
            </FormControl>

            <FormControl :error="fieldErrors.invoice_expires_in_minutes">
                <div class="flex items-center justify-between">
                    <Label for="merchant-invoice-expires" required>
                        {{ __('frontend.merchants.fields.invoice_expires_in_minutes') }}
                    </Label>
                </div>
                <Input
                    id="merchant-invoice-expires"
                    v-model="form.invoice_expires_in_minutes"
                    type="number"
                    min="1"
                    max="10080"
                    :placeholder="__('frontend.merchants.fields.invoice_expires_in_minutes_placeholder')"
                    required
                />
            </FormControl>

            <FormControl :error="fieldErrors.logo">
                <div class="flex items-center justify-between gap-2">
                    <Label for="merchant-logo">{{ __('frontend.merchants.fields.logo') }}</Label>
                    <span class="text-xs opacity-70">{{ __('frontend.merchants.fields.logo_hint') }}</span>
                </div>
                <div v-if="props.mode === 'edit' && props.currentLogoUrl" class="flex items-center gap-3 rounded-xl border border-base-200 p-3 mb-2">
                    <div class="avatar">
                        <div class="w-14 h-14 rounded-full ring ring-primary/30 ring-offset-2 ring-offset-base-100 overflow-hidden">
                            <img :src="props.currentLogoUrl" alt="logo" class="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div class="text-sm text-base-content/80">
                        {{ __('frontend.merchants.fields.logo_current') }}
                    </div>
                </div>
                <FileInput
                    id="merchant-logo"
                    v-model="form.logo"
                    accept="image/png,image/jpeg,image/webp"
                    clearable
                />
                <div v-if="form.logo" class="flex items-center justify-between text-xs text-base-content/70 mt-1">
                    <span class="truncate">{{ form.logo.name }}</span>
                </div>
            </FormControl>

            <FormControl :error="fieldErrors.white_label_enabled">
                <div class="flex items-center justify-between">
                    <Label for="merchant-white-label">{{ __('frontend.merchants.fields.white_label_enabled') }}</Label>
                </div>
                <div class="flex items-center gap-3 mt-1">
                    <input
                        id="merchant-white-label"
                        v-model="form.white_label_enabled"
                        type="checkbox"
                        class="toggle toggle-primary"
                    />
                    <span class="text-sm text-base-content/80">
                        {{
                            form.white_label_enabled
                                ? __('frontend.merchants.fields.white_label_enabled_on')
                                : __('frontend.merchants.fields.white_label_enabled_off')
                        }}
                    </span>
                </div>
            </FormControl>

            <div class="modal-action">
                <button type="button" class="btn" @click="close" :disabled="loading">{{ __('frontend.common.cancel') }}</button>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                    <span v-if="loading" class="loading loading-spinner loading-sm mr-2" />
                    {{ props.mode === 'edit' ? __('frontend.merchants.actions.save') : __('frontend.merchants.actions.add') }}
                </button>
            </div>
        </form>
    </ModalDialog>
</template>

