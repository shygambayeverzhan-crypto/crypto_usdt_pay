<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/ui/Pagination.vue';
import ModalDialog from '@/components/ui/modal/ModalDialog.vue';
import DateTimeFormat from '@/components/ui/DateTimeFormat.vue';
import { vueLang } from '@erag/lang-sync-inertia';
import FilterPanel from '@/components/filters/FilterPanel.vue';
import EditButton from '@/components/ui/table-actions/EditButton.vue';

type RoleOption = { value: string; label: string };
type FilterType = 'text' | 'select' | 'checkpoint';

type UserItem = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    email_verified_at?: string | null;
    approved_at?: string | null;
    created_at?: string | null;
    can_manage_role: boolean;
    can_be_impersonated: boolean;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type PaginatedUsers = { data: UserItem[]; links: PaginationLink[] };
type UserFilters = { search: string; role: string };
type FilterField = { key: keyof UserFilters; type: FilterType; label: string; placeholder?: string; options?: { value: string; label: string }[] };

const page = usePage();
const users = computed(() => page.props.users as PaginatedUsers);
const roleOptions = computed(() => page.props.roleOptions as RoleOption[]);
const pageFilters = computed(() => (page.props.filters as Partial<UserFilters> | undefined) ?? {});
const { __ } = vueLang();

const showCreate = ref(false);
const showEdit = ref(false);
const selectedUser = ref<UserItem | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const approvalForm = useForm({
    approved: false,
});

const impersonateForm = useForm({});
const filterDefaults: UserFilters = {
    search: '',
    role: '',
};
const filters = useForm<UserFilters>({
    ...filterDefaults,
    ...pageFilters.value,
});
const filtersModel = computed({
    get: () => ({
        ...filterDefaults,
        ...filters.data(),
    }),
    set: (value: UserFilters) => {
        filters.search = value.search ?? '';
        filters.role = value.role ?? '';
    },
});
const filterFields = computed<FilterField[]>(() => [
    {
        key: 'search',
        type: 'text',
        label: __('frontend.admin_users.filters.search'),
        placeholder: __('frontend.admin_users.filters.search_placeholder'),
    },
    {
        key: 'role',
        type: 'select',
        label: __('frontend.admin_users.filters.role'),
        options: roleOptions.value,
    },
]);

function buildFilterPayload(extra: Record<string, unknown> = {}) {
    const payload = {
        ...filters.data(),
        ...extra,
    };

    const cleaned: Record<string, unknown> = {};

    Object.entries(payload).forEach(([key, value]) => {
        if (key === 'page') {
            cleaned[key] = value;
            return;
        }

        if (value !== undefined && value !== null && String(value).length > 0) {
            cleaned[key] = value;
        }
    });

    return cleaned;
}

function applyFilters() {
    filters
        .transform(() => buildFilterPayload({ page: 1 }))
        .get('/admin/users', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => filters.transform(data => data),
        });
}

function resetFilters() {
    filters.search = filterDefaults.search;
    filters.role = filterDefaults.role;

    applyFilters();
}

function openCreate() {
    createForm.reset();
    createForm.role = 'user';
    showCreate.value = true;
}

function submitCreate() {
    createForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
        onError: () => {
            showCreate.value = true;
        },
    });
}

function openEdit(user: UserItem) {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.role = user.roles[0] ?? 'user';
    editForm.clearErrors();
    approvalForm.approved = Boolean(user.approved_at);
    approvalForm.clearErrors();
    showEdit.value = true;
}

function submitEdit() {
    if (!selectedUser.value) return;
    const allowRoleChange = selectedUser.value.can_manage_role;
    editForm.transform((data) => {
        if (allowRoleChange) {
            return data;
        }
        const { role, ...rest } = data;
        return { ...rest, role: null };
    });
    editForm.patch(`/admin/users/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEdit.value = false;
            editForm.password = '';
            editForm.password_confirmation = '';
        },
        onError: () => {
            showEdit.value = true;
        },
        onFinish: () => {
            editForm.transform((data) => data);
        },
    });
}

function toggleApproval() {
    if (!selectedUser.value) return;
    if (approvalForm.processing) return;

    approvalForm.patch(`/admin/users/${selectedUser.value.id}/approval`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (!selectedUser.value) return;
            selectedUser.value.approved_at = approvalForm.approved ? new Date().toISOString() : null;
        },
    });
}
function closeEdit() {
    showEdit.value = false;
    editForm.clearErrors();
    approvalForm.clearErrors();
}

function closeCreate() {
    showCreate.value = false;
    createForm.clearErrors();
}

function impersonate(user: UserItem) {
    impersonateForm.post(`/admin/impersonate/${user.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[{ title: __('frontend.admin_users.breadcrumb.home'), href: '/' }, { title: __('frontend.admin_users.breadcrumb.users'), href: '/admin/users' }]"
        :title="__('frontend.admin_users.title')"
    >
        <template #header-actions>
            <button class="btn btn-primary btn-sm" @click="openCreate">
                <span class="hidden sm:block">{{ __('frontend.admin_users.actions.create_user') }}</span>
                <span class="block sm:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </span>
            </button>
        </template>

        <div class="grid gap-6">
            <FilterPanel
                v-model="filtersModel"
                :fields="filterFields"
                :title="__('frontend.admin_users.filters.title')"
                :apply-label="__('frontend.admin_users.filters.apply')"
                :reset-label="__('frontend.admin_users.filters.reset')"
                :show-label="__('frontend.admin_users.filters.show')"
                :hide-label="__('frontend.admin_users.filters.hide')"
                :any-option-label="__('frontend.admin_users.filters.any')"
                :loading="filters.processing"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <div class="lg:card lg:bg-base-100 lg:shadow">
                <div class="lg:card-body">
                    <h2 class="hidden lg:block card-title">{{ __('frontend.admin_users.list_title') }}</h2>
                    <h2 class="lg:hidden card-title mb-3">{{ __('frontend.admin_users.list_title') }}</h2>

                    <div class="hidden lg:block overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('frontend.admin_users.table.id') }}</th>
                                    <th>{{ __('frontend.admin_users.table.name') }}</th>
                                    <th>{{ __('frontend.admin_users.table.email') }}</th>
                                    <th>{{ __('frontend.admin_users.table.roles') }}</th>
                                    <th>{{ __('frontend.admin_users.table.approval') }}</th>
                                    <th>{{ __('frontend.admin_users.table.created') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.data" :key="user.id">
                                    <td class="font-mono text-xs">#{{ user.id }}</td>
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="role in user.roles" :key="role" class="badge badge-ghost badge-sm uppercase">{{ role }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span v-if="user.approved_at" class="badge badge-success badge-sm">{{ __('frontend.admin_users.table.approved') }}</span>
                                        <span v-else class="badge badge-warning badge-sm">{{ __('frontend.admin_users.table.pending') }}</span>
                                    </td>
                                    <td class="text-xs font-mono">
                                        <DateTimeFormat :value="user.created_at || ''" />
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <EditButton :title="__('frontend.common.edit')" @click="openEdit(user)" />
                                            <button
                                                v-if="user.can_be_impersonated"
                                                class="btn btn-xs btn-warning"
                                                :disabled="impersonateForm.processing"
                                                @click="impersonate(user)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!users.data.length">
                                    <td colspan="6" class="text-center text-sm opacity-70 py-6">{{ __('frontend.admin_users.table.empty') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="lg:hidden space-y-3">
                        <div v-for="user in users.data" :key="user.id" class="card bg-base-100 shadow-sm">
                            <div class="card-body p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold">{{ user.name }}</div>
                                    <EditButton :title="__('frontend.common.edit')" @click="openEdit(user)" />
                                </div>
                                    <div class="text-sm opacity-80">{{ user.email }}</div>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role" class="badge badge-ghost badge-sm uppercase">{{ role }}</span>
                                </div>
                                <div>
                                    <span v-if="user.approved_at" class="badge badge-success badge-sm">{{ __('frontend.admin_users.table.approved') }}</span>
                                    <span v-else class="badge badge-warning badge-sm">{{ __('frontend.admin_users.table.pending') }}</span>
                                </div>
                                <div class="text-xs opacity-70">
                                    <DateTimeFormat :value="user.created_at || ''" />
                                </div>
                                <button
                                    v-if="user.can_be_impersonated"
                                    class="btn btn-warning btn-sm w-full"
                                    :disabled="impersonateForm.processing"
                                    @click="impersonate(user)"
                                >
                                    {{ __('frontend.admin_users.actions.impersonate') }}
                                </button>
                            </div>
                        </div>
                        <div v-if="!users.data.length" class="text-center text-sm opacity-70 py-6">
                            {{ __('frontend.admin_users.table.empty') }}
                        </div>
                    </div>

                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>

        <ModalDialog v-model="showCreate" :title="__('frontend.admin_users.forms.create_title')">
            <form class="grid gap-4" @submit.prevent="submitCreate">
                <div class="grid gap-2">
                    <label class="label text-sm" for="create-name">{{ __('frontend.admin_users.forms.name') }}</label>
                    <input id="create-name" v-model="createForm.name" type="text" class="input input-bordered w-full" :placeholder="__('frontend.admin_users.forms.name')" />
                    <p v-if="createForm.errors.name" class="text-error text-sm">{{ createForm.errors.name }}</p>
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="create-email">{{ __('frontend.admin_users.table.email') }}</label>
                    <input id="create-email" v-model="createForm.email" type="email" class="input input-bordered w-full" placeholder="email@example.com" />
                    <p v-if="createForm.errors.email" class="text-error text-sm">{{ createForm.errors.email }}</p>
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="create-password">{{ __('frontend.admin_users.forms.password') }}</label>
                    <input id="create-password" v-model="createForm.password" type="password" class="input input-bordered w-full" :placeholder="__('frontend.admin_users.forms.password')" />
                    <p v-if="createForm.errors.password" class="text-error text-sm">{{ createForm.errors.password }}</p>
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="create-password-confirmation">{{ __('frontend.admin_users.forms.password_confirmation') }}</label>
                    <input id="create-password-confirmation" v-model="createForm.password_confirmation" type="password" class="input input-bordered w-full" :placeholder="__('frontend.admin_users.forms.password_confirmation')" />
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="create-role">{{ __('frontend.admin_users.forms.role') }}</label>
                    <select id="create-role" v-model="createForm.role" class="select select-bordered w-full">
                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                    </select>
                    <p v-if="createForm.errors.role" class="text-error text-sm">{{ createForm.errors.role }}</p>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" @click="closeCreate">{{ __('frontend.admin_users.actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" :disabled="createForm.processing">
                        <span v-if="createForm.processing" class="loading loading-spinner loading-sm mr-2" />
                        {{ __('frontend.admin_users.actions.create') }}
                    </button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showEdit" :title="__('frontend.admin_users.forms.edit_title')" @close="closeEdit">
            <form v-if="selectedUser" class="grid gap-4" @submit.prevent="submitEdit">
                <div class="grid gap-2">
                    <label class="label text-sm" for="edit-name">{{ __('frontend.admin_users.forms.name') }}</label>
                    <input id="edit-name" v-model="editForm.name" type="text" class="input input-bordered w-full" />
                    <p v-if="editForm.errors.name" class="text-error text-sm">{{ editForm.errors.name }}</p>
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="edit-email">{{ __('frontend.admin_users.table.email') }}</label>
                    <input id="edit-email" v-model="editForm.email" type="email" class="input input-bordered w-full" />
                    <p v-if="editForm.errors.email" class="text-error text-sm">{{ editForm.errors.email }}</p>
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="edit-password">{{ __('frontend.admin_users.forms.new_password') }}</label>
                    <input id="edit-password" v-model="editForm.password" type="password" class="input input-bordered w-full" :placeholder="__('frontend.admin_users.forms.new_password_placeholder')" />
                    <p v-if="editForm.errors.password" class="text-error text-sm">{{ editForm.errors.password }}</p>
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="edit-password-confirmation">{{ __('frontend.admin_users.forms.password_confirmation') }}</label>
                    <input id="edit-password-confirmation" v-model="editForm.password_confirmation" type="password" class="input input-bordered w-full" :placeholder="__('frontend.admin_users.forms.confirm_placeholder')" />
                </div>

                <div class="grid gap-2">
                    <label class="label text-sm" for="edit-role">{{ __('frontend.admin_users.forms.role') }}</label>
                    <select
                        id="edit-role"
                        v-model="editForm.role"
                        class="select select-bordered w-full"
                        :disabled="!selectedUser.can_manage_role"
                    >
                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                    </select>
                    <p v-if="editForm.errors.role" class="text-error text-sm">{{ editForm.errors.role }}</p>
                    <p v-if="!selectedUser.can_manage_role" class="text-xs opacity-70">
                        {{ __('frontend.admin_users.forms.cannot_change_own_role') }}
                    </p>
                </div>

                <div v-if="!selectedUser.roles.includes('admin')" class="rounded-xl border border-base-200 bg-base-200/40 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold">{{ __('frontend.admin_users.table.approval') }}</div>
                            <div class="text-xs opacity-70">
                                {{ approvalForm.approved ? __('frontend.admin_users.table.approved') : __('frontend.admin_users.table.pending') }}
                            </div>
                        </div>
                        <input
                            v-model="approvalForm.approved"
                            type="checkbox"
                            class="toggle toggle-success"
                            :disabled="approvalForm.processing"
                            @change="toggleApproval"
                        />
                    </div>
                    <p v-if="approvalForm.errors.approved" class="text-error text-sm mt-2">{{ approvalForm.errors.approved }}</p>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" @click="closeEdit">{{ __('frontend.admin_users.actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" :disabled="editForm.processing">
                        <span v-if="editForm.processing" class="loading loading-spinner loading-sm mr-2" />
                        {{ __('frontend.admin_users.actions.save') }}
                    </button>
                </div>
            </form>
        </ModalDialog>
    </AppLayout>
</template>


