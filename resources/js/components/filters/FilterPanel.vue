<script setup lang="ts">
import { computed, ref } from 'vue';
import Input from '@/components/form/Input.vue';
import Select from '@/components/form/Select.vue';
import Label from '@/components/form/Label.vue';

type FilterType = 'text' | 'select' | 'checkpoint';

interface Option {
  value: string | number;
  label: string;
}

interface FilterField {
  key: string;
  label: string;
  type: FilterType;
  placeholder?: string;
  options?: Option[];
}

interface Props {
  modelValue: Record<string, unknown>;
  fields: FilterField[];
  title: string;
  applyLabel: string;
  resetLabel: string;
  showLabel: string;
  hideLabel: string;
  anyOptionLabel: string;
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => ({}),
  fields: () => [],
  loading: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, unknown>): void;
  (e: 'apply'): void;
  (e: 'reset'): void;
}>();

const collapsed = ref(true);
const storageKey = computed(() => {
  if (typeof window === 'undefined') {
    return null;
  }

  return `filter-panel-collapsed:${encodeURIComponent(window.location.pathname)}`;
});

function readCollapsedState() {
  if (typeof document === 'undefined') {
    return true;
  }

  const key = storageKey.value;
  if (!key) {
    return true;
  }

  const cookies = document.cookie ? document.cookie.split(';') : [];
  const matched = cookies
    .map((cookie) => cookie.trim())
    .find((cookie) => cookie.startsWith(`${key}=`));

  if (!matched) {
    return true;
  }

  const [, value] = matched.split('=');
  return value === '1';
}

function persistCollapsedState(value: boolean) {
  if (typeof document === 'undefined') {
    return;
  }

  const key = storageKey.value;
  if (!key) {
    return;
  }

  document.cookie = `${key}=${value ? '1' : '0'}; path=/`;
}

if (typeof document !== 'undefined') {
  collapsed.value = readCollapsedState();
}

const hasActiveFilters = computed(() => {
  return props.fields.some((field) => {
    const value = props.modelValue?.[field.key];
    if (Array.isArray(value)) {
      return value.length > 0;
    }
    if (typeof value === 'boolean') {
      return value;
    }
    return value !== undefined && value !== null && String(value).length > 0;
  });
});

function updateField(key: string, value: unknown) {
  emit('update:modelValue', {
    ...(props.modelValue || {}),
    [key]: value,
  });
}

function toggle() {
  const nextValue = !collapsed.value;
  collapsed.value = nextValue;
  persistCollapsedState(nextValue);
}

function submit() {
  emit('apply');
}

function reset() {
  emit('reset');
}
</script>

<template>
  <div class="lg:card lg:bg-base-100 lg:shadow">
    <div class="lg:card-body">
      <div class="flex items-center justify-between gap-4">
        <h2 class="card-title">{{ props.title }}</h2>
        <button type="button" class="btn btn-ghost btn-sm relative" @click="toggle">
          <span v-if="collapsed">{{ props.showLabel }}</span>
          <span v-else>{{ props.hideLabel }}</span>
          <span
            v-if="collapsed && hasActiveFilters"
            class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-error"
          />
        </button>
      </div>

      <form v-if="!collapsed" class="mt-4 space-y-4" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="field in props.fields" :key="field.key" class="form-control gap-2">
            <template v-if="field.type !== 'checkpoint'">
              <Label :for="`filter-${field.key}`" class="label-text">{{ field.label }}</Label>

              <Input
                v-if="field.type === 'text'"
                :id="`filter-${field.key}`"
                :name="field.key"
                :model-value="(props.modelValue?.[field.key] as string | number | '') ?? ''"
                :placeholder="field.placeholder"
                autocomplete="off"
                @update:model-value="updateField(field.key, $event)"
                @keyup.enter.prevent="submit"
              />

              <Select
                v-else
                :id="`filter-${field.key}`"
                :name="field.key"
                :model-value="(props.modelValue?.[field.key] as string | number | '') ?? ''"
                :options="[{ value: '', label: props.anyOptionLabel }, ...(field.options ?? [])]"
                @update:model-value="updateField(field.key, $event)"
              />
            </template>

            <template v-else>
              <div class="flex items-center gap-3 cursor-pointer h-full min-h-[3rem]">
                <input
                  :id="`filter-${field.key}`"
                  type="checkbox"
                  class="toggle toggle-primary"
                  :checked="Boolean(props.modelValue?.[field.key])"
                  @change="updateField(field.key, ($event.target as HTMLInputElement).checked)"
                />
                <span class="label-text">{{ field.label }}</span>
              </div>
            </template>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary" :disabled="props.loading">
            {{ props.applyLabel }}
          </button>
          <button type="button" class="btn btn-ghost" :disabled="props.loading" @click="reset">
            {{ props.resetLabel }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>


