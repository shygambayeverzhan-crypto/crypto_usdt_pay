<script setup lang="ts">
import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="hero min-h-svh bg-base-200 p-6 md:p-10">
        <div class="hero-content w-full max-w-sm">
            <div class="w-full">
                <div class="card w-full bg-base-100 shadow-xl">
                    <div class="card-body gap-6">
                        <div class="flex flex-col items-center gap-4">
                            <div class="text-center">
                                <h1 class="card-title justify-center text-xl font-medium">{{ title }}</h1>
                                <p class="text-base-content/70 text-sm">{{ description }}</p>
                            </div>
                        </div>
                        <slot />
                    </div>
                </div>
                <div class="flex w-full items-center justify-center gap-2 border-t border-base-200 pt-4">
                    <LanguageSwitcher :full-width="false" />
                    <ThemeSwitcher />
                </div>
            </div>
        </div>
    </div>
</template>
