<script setup lang="ts">
import ApiDocumentation from '@/pages/api/elements/ApiDocumentation.vue'
import ApiCallbacksInfo from '@/pages/api/elements/ApiCallbacksInfo.vue'
import CreateInvoiceRequest from '@/pages/api/elements/requests/CreateInvoiceRequest.vue'
import ListInvoicesRequest from '@/pages/api/elements/requests/ListInvoicesRequest.vue'
import GetInvoiceRequest from '@/pages/api/elements/requests/GetInvoiceRequest.vue'
import GetInvoiceStatusRequest from '@/pages/api/elements/requests/GetInvoiceStatusRequest.vue'
import GetInvoicePublicRequest from '@/pages/api/elements/requests/GetInvoicePublicRequest.vue'
import GetInvoiceQrRequest from '@/pages/api/elements/requests/GetInvoiceQrRequest.vue'
import CancelInvoiceRequest from '@/pages/api/elements/requests/CancelInvoiceRequest.vue'
import ListMerchantsRequest from '@/pages/api/elements/requests/ListMerchantsRequest.vue'
import ListClientsRequest from '@/pages/api/elements/requests/ListClientsRequest.vue'
import CreateClientRequest from '@/pages/api/elements/requests/CreateClientRequest.vue'

interface Props {
    apiKey: string;
    apiBase: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="space-y-4">
        <ApiDocumentation :api-base="props.apiBase" />
        <CreateInvoiceRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <ListInvoicesRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <GetInvoiceRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <GetInvoiceStatusRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <GetInvoicePublicRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <GetInvoiceQrRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <CancelInvoiceRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <ListMerchantsRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <ListClientsRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <CreateClientRequest :api-key="props.apiKey" :api-base="props.apiBase" />
        <ApiCallbacksInfo />
    </div>
</template>