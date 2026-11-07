<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { vueLang } from '@erag/lang-sync-inertia';

type AllowedIp = {
    id: number;
    ip: string;
    created_at: string | null;
}

interface Props {
    apiTokenId: number | string | null;
    allowedIps: AllowedIp[];
}

const props = defineProps<Props>();
const { __ } = vueLang();

const ipValue = ref('');
const error = ref<string>('');
const saving = ref(false);
const deletingId = ref<number | null>(null);
const items = ref<AllowedIp[]>([...props.allowedIps]);

function normalizeIp(value: string): string
{
    return value.trim().toLowerCase();
}

async function addIp() {
    const prepared = normalizeIp(ipValue.value);

    if (!prepared) {
        error.value = __('frontend.api.whitelist.validation_required');
        return;
    }

    if (!props.apiTokenId) {
        error.value = __('frontend.api.whitelist.generic_error');
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        const response = await axios.post('/api/allowed-ips', {
            api_token_id: props.apiTokenId,
            ip: prepared,
        });

        items.value = response.data?.allowed_ips ?? [];
        ipValue.value = '';
    } catch (e: any) {
        error.value =
            e?.response?.data?.errors?.ip?.[0] ??
            e?.response?.data?.message ??
            __('frontend.api.whitelist.generic_error');
    } finally {
        saving.value = false;
    }
}

async function removeIp(id: number) {
    if (!props.apiTokenId) {
        error.value = __('frontend.api.whitelist.generic_error');
        return;
    }

    deletingId.value = id;
    error.value = '';

    try {
        const response = await axios.delete(`/api/allowed-ips/${id}`);
        items.value = response.data?.allowed_ips ?? [];
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? __('frontend.api.whitelist.generic_error');
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-3">
            <div class="space-y-1">
                <h3 class="card-title">{{ __('frontend.api.whitelist.title') }}</h3>
                <p class="text-sm text-base-content/70">
                    {{ __('frontend.api.whitelist.subtitle') }}
                </p>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">{{ __('frontend.api.whitelist.field_label') }}</span>
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input
                        class="input input-md w-full sm:flex-1"
                        v-model="ipValue"
                        :placeholder="__('frontend.api.whitelist.placeholder')"
                        @keyup.enter="addIp"
                    />
                    <button class="btn btn-primary sm:w-48" :disabled="saving" @click="addIp">
                        <span v-if="saving" class="loading loading-spinner"></span>
                        <span v-else>{{ __('frontend.api.whitelist.add_button') }}</span>
                    </button>
                </div>
                <p v-if="error" class="text-error text-sm mt-2">{{ error }}</p>
            </div>

            <div class="space-y-3">
                <div v-if="items.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="item in items" :key="item.id" class="border rounded-xl p-4 flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <div class="font-mono text-sm break-all">{{ item.ip }}</div>
                            <div v-if="item.created_at" class="text-xs text-base-content/60">
                                {{ __('frontend.api.whitelist.created_at', { date: item.created_at }) }}
                            </div>
                        </div>
                        <button
                            class="btn btn-ghost btn-sm text-error"
                            :disabled="deletingId === item.id"
                            @click="removeIp(item.id)"
                        >
                            <span v-if="deletingId === item.id" class="loading loading-spinner loading-xs"></span>
                            <span v-else>✕</span>
                        </button>
                    </div>
                </div>

                <div v-else class="text-sm text-base-content/60">
                    {{ __('frontend.api.whitelist.empty') }}
                </div>
            </div>
        </div>
    </div>
</template>


