<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { vueLang } from '@erag/lang-sync-inertia';

const themes = ['light', 'dark'] as const;

type ThemeName = typeof themes[number];

const { __ } = vueLang();

const currentTheme = ref<ThemeName>('dark');

const isDark = computed(() => currentTheme.value === 'dark');
const label = computed(() => (isDark.value ? __('frontend.theme_switcher.dark') : __('frontend.theme_switcher.light')));

function isThemeName(value: string | null): value is ThemeName {
    return value === 'light' || value === 'dark';
}

function applyTheme(theme: ThemeName) {
    document.documentElement.setAttribute('data-theme', theme);
    currentTheme.value = theme;

    try {
        window.localStorage.setItem('theme', theme);
    } catch (e) {
        // ignore storage errors
    }
}

function toggleTheme() {
    applyTheme(isDark.value ? 'light' : 'dark');
}

onMounted(() => {
    const savedTheme = window.localStorage.getItem('theme');
    const htmlTheme = document.documentElement.getAttribute('data-theme');
    const theme = isThemeName(savedTheme) ? savedTheme : (isThemeName(htmlTheme) ? htmlTheme : 'dark');

    applyTheme(theme);
});
</script>

<template>
    <button
        type="button"
        class="btn btn-ghost btn-square shrink-0 rounded-xl border border-base-300/70 bg-base-200/60 shadow-sm transition hover:border-primary/50 hover:bg-base-200"
        :aria-label="label"
        :title="label"
        @click="toggleTheme"
    >
        <span class="relative grid size-6 place-items-center overflow-hidden">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                class="absolute size-5 text-amber-500 transition duration-300"
                :class="isDark ? 'scale-75 rotate-90 opacity-0' : 'scale-100 rotate-0 opacity-100'"
                aria-hidden="true"
            >
                <path d="M11 1C11 0.447715 11.4477 0 12 0C12.5523 0 13 0.447715 13 1V3C13 3.55228 12.5523 4 12 4C11.4477 4 11 3.55228 11 3V1Z" fill="currentColor" />
                <path fill-rule="evenodd" clip-rule="evenodd" d="M18 12C18 15.3137 15.3137 18 12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12ZM8.06167 12C8.06167 14.1751 9.82492 15.9383 12 15.9383C14.1751 15.9383 15.9383 14.1751 15.9383 12C15.9383 9.82492 14.1751 8.06167 12 8.06167C9.82492 8.06167 8.06167 9.82492 8.06167 12Z" fill="currentColor" />
                <path d="M20.4853 3.51472C20.0947 3.12419 19.4616 3.12419 19.0711 3.51472L17.6568 4.92893C17.2663 5.31946 17.2663 5.95262 17.6568 6.34315C18.0474 6.73367 18.6805 6.73367 19.0711 6.34315L20.4853 4.92893C20.8758 4.53841 20.8758 3.90524 20.4853 3.51472Z" fill="currentColor" />
                <path d="M1 13C0.447715 13 0 12.5523 0 12C0 11.4477 0.447715 11 1 11H3C3.55228 11 4 11.4477 4 12C4 12.5523 3.55228 13 3 13H1Z" fill="currentColor" />
                <path d="M3.51472 3.51472C3.1242 3.90524 3.1242 4.53841 3.51472 4.92893L4.92894 6.34315C5.31946 6.73367 5.95263 6.73367 6.34315 6.34315C6.73368 5.95262 6.73368 5.31946 6.34315 4.92893L4.92894 3.51472C4.53841 3.12419 3.90525 3.12419 3.51472 3.51472Z" fill="currentColor" />
                <path d="M11 21C11 20.4477 11.4477 20 12 20C12.5523 20 13 20.4477 13 21V23C13 23.5523 12.5523 24 12 24C11.4477 24 11 23.5523 11 23V21Z" fill="currentColor" />
                <path d="M6.34315 17.6569C5.95263 17.2663 5.31946 17.2663 4.92894 17.6569L3.51473 19.0711C3.1242 19.4616 3.1242 20.0948 3.51473 20.4853C3.90525 20.8758 4.53842 20.8758 4.92894 20.4853L6.34315 19.0711C6.73368 18.6805 6.73368 18.0474 6.34315 17.6569Z" fill="currentColor" />
                <path d="M21 13C20.4477 13 20 12.5523 20 12C20 11.4477 20.4477 11 21 11H23C23.5523 11 24 11.4477 24 12C24 12.5523 23.5523 13 23 13H21Z" fill="currentColor" />
                <path d="M17.6568 17.6569C17.2663 18.0474 17.2663 18.6805 17.6568 19.0711L19.0711 20.4853C19.4616 20.8758 20.0947 20.8758 20.4853 20.4853C20.8758 20.0948 20.8758 19.4616 20.4853 19.0711L19.0711 17.6569C18.6805 17.2663 18.0474 17.2663 17.6568 17.6569Z" fill="currentColor" />
            </svg>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                class="absolute size-5 text-indigo-300 transition duration-300"
                :class="isDark ? 'scale-100 rotate-0 opacity-100' : 'scale-75 -rotate-90 opacity-0'"
                aria-hidden="true"
            >
                <path fill-rule="evenodd" clip-rule="evenodd" d="M11.0174 2.80157C6.37072 3.29221 2.75 7.22328 2.75 12C2.75 17.1086 6.89137 21.25 12 21.25C16.7767 21.25 20.7078 17.6293 21.1984 12.9826C19.8717 14.6669 17.8126 15.75 15.5 15.75C11.4959 15.75 8.25 12.5041 8.25 8.5C8.25 6.18738 9.33315 4.1283 11.0174 2.80157ZM1.25 12C1.25 6.06294 6.06294 1.25 12 1.25C12.7166 1.25 13.0754 1.82126 13.1368 2.27627C13.196 2.71398 13.0342 3.27065 12.531 3.57467C10.8627 4.5828 9.75 6.41182 9.75 8.5C9.75 11.6756 12.3244 14.25 15.5 14.25C17.5882 14.25 19.4172 13.1373 20.4253 11.469C20.7293 10.9658 21.286 10.804 21.7237 10.8632C22.1787 10.9246 22.75 11.2834 22.75 12C22.75 17.9371 17.9371 22.75 12 22.75C6.06294 22.75 1.25 17.9371 1.25 12Z" fill="currentColor" />
            </svg>
        </span>
    </button>
</template>
