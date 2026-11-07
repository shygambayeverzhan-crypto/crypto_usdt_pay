<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';
import ApiTokens from './elements/ApiTokens.vue';
import ApiRequests from './elements/ApiRequests.vue';
import { vueLang } from '@erag/lang-sync-inertia';
import ApiAllowedIps from './elements/ApiAllowedIps.vue';

type PageProps = {
    publicApiKey: string;
    callbackToken: string;
    apiBaseUrl: string;
    apiTokenId: number | string | null;
    allowedIps: AllowedIp[];
}

type AllowedIp = {
    id: number;
    ip: string;
    created_at: string | null;
}

const { __ } = vueLang();

const breadcrumbs: BreadcrumbItem[] = [
    { title: __('frontend.api.breadcrumb'), href: '/api' },
];

const page = usePage();
const props = computed(() => page.props as unknown as PageProps);

const apiKey = computed(() => props.value.publicApiKey || '');
const callbackToken = computed(() => props.value.callbackToken || '');
const apiBase = computed(() => props.value.apiBaseUrl || '/api/v1');
const apiTokenId = computed(() => props.value.apiTokenId);
const allowedIps = computed(() => props.value.allowedIps || []);

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mt-6 space-y-8">
            <ApiTokens :api-key="apiKey" :callback-token="callbackToken" :api-base="apiBase" />
            <ApiAllowedIps :api-token-id="apiTokenId" :allowed-ips="allowedIps" />
            <ApiRequests :api-key="apiKey" :api-base="apiBase" />
        </div>
    </AppLayout>
</template>




