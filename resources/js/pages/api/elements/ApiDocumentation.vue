<script setup lang="ts">
import { vueLang } from '@erag/lang-sync-inertia';

interface Props {
    apiBase: string;
}

const { __ } = vueLang();
defineProps<Props>();
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <h3 class="card-title">{{ __('frontend.api.documentation.title') }}</h3>
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h3 class="font-bold">{{ __('frontend.api.documentation.subtitle_title') }}</h3>
                    <div class="text-xs">{{ __('frontend.api.documentation.subtitle', { base: apiBase }) }}</div>
                </div>
            </div>
            <div class="alert alert-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                <div>
                    <h3 class="font-bold">{{ __('frontend.api.documentation.accept_title') }}</h3>
                    <div class="text-xs">{{ __('frontend.api.documentation.accept_hint') }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

