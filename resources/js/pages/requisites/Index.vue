<script setup lang="ts">
import Pagination from '@/components/ui/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Check,
    CreditCard,
    Landmark,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

type Option = { value: string; label: string };
type Requisite = {
    id: number;
    label: string;
    method: 'card' | 'usdt';
    currency: string;
    bank: string | null;
    bank_name: string | null;
    holder: string | null;
    number_masked: string | null;
    address: string | null;
    network: string | null;
    min_amount: string;
    max_amount: string;
    liquidity_limit: string | null;
    is_active: boolean;
};
const props = defineProps<{
    requisites: {
        data: Requisite[];
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    banks: Option[];
    networks: Option[];
    currencies: Option[];
    filters?: { method: 'card' | 'usdt' | null };
}>();
const page = usePage();
const en = computed(() => page.props.locale === 'en');
const t = (ru: string, english: string) => (en.value ? english : ru);
const modal = ref<HTMLDialogElement>();
const deleteModal = ref<HTMLDialogElement>();
const editing = ref<Requisite | null>(null);
const deleting = ref<Requisite | null>(null);
const busyId = ref<number | null>(null);
const operationError = ref('');
const filter = computed(() => props.filters?.method ?? 'all');
const items = computed(() => props.requisites.data);
function changeFilter(method: string) {
    router.get('/requisites', method === 'all' ? {} : { method }, {
        preserveScroll: true,
        preserveState: true,
    });
}
const form = useForm({
    label: '',
    method: 'card' as 'card' | 'usdt',
    currency: 'KZT',
    bank: '',
    bank_name: '',
    holder: '',
    number: '',
    address: '',
    network: 'tron',
    min_amount: '',
    max_amount: '',
    liquidity_limit: '',
    is_active: false,
});
const amountFields = computed(() => [
    {
        key: 'min_amount' as const,
        label: t('Минимальная сумма', 'Minimum amount'),
        placeholder: form.method === 'card' ? '6 000' : '10',
        required: true,
    },
    {
        key: 'max_amount' as const,
        label: t('Максимальная сумма', 'Maximum amount'),
        placeholder: form.method === 'card' ? '450 000' : '1 000',
        required: true,
    },
    {
        key: 'liquidity_limit' as const,
        label: t('Лимит ликвидности', 'Liquidity limit'),
        placeholder: t('Без лимита', 'No limit'),
        required: false,
    },
]);
const bankLabel = (item: Requisite) =>
    item.bank === 'other'
        ? item.bank_name
        : props.banks.find((bank) => bank.value === item.bank)?.label;
const networkLabel = (network: string | null) =>
    props.networks.find((item) => item.value === network)?.label ?? network;
const prettyAmount = (value: string | null) => {
    if (value === null) return '—';
    const [whole, decimal] = value.split('.');
    const fraction = decimal?.replace(/0+$/, '');
    return (
        whole.replace(/\B(?=(\d{3})+(?!\d))/g, '\u202f') +
        (fraction ? '.' + fraction : '')
    );
};

async function openEditor(
    item: Requisite | null = null,
    method: 'card' | 'usdt' = 'card',
) {
    editing.value = item;
    form.reset();
    form.clearErrors();
    form.method = item?.method ?? method;
    form.currency = item?.currency ?? (method === 'card' ? 'KZT' : 'USDT');
    if (item) {
        form.label = item.label;
        form.bank = item.bank ?? '';
        form.bank_name = item.bank_name ?? '';
        form.holder = item.holder ?? '';
        form.address = item.address ?? '';
        form.network = item.network ?? 'tron';
        form.min_amount = item.min_amount;
        form.max_amount = item.max_amount;
        form.liquidity_limit = item.liquidity_limit ?? '';
        form.is_active = item.is_active;
    }
    await nextTick();
    modal.value?.showModal();
}
function changeMethod(method: 'card' | 'usdt') {
    if (form.method === method) return;
    form.method = method;
    form.currency = method === 'card' ? 'KZT' : 'USDT';
    form.bank = '';
    form.bank_name = '';
    form.holder = '';
    form.number = '';
    form.address = '';
    form.network = 'tron';
    form.min_amount = '';
    form.max_amount = '';
    form.liquidity_limit = '';
    form.clearErrors();
}
function closeEditor() {
    if (form.processing) return;
    modal.value?.close();
    form.reset();
    form.clearErrors();
}
function save() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modal.value?.close();
            form.reset();
            form.clearErrors();
        },
        onError: () =>
            nextTick(() =>
                modal.value
                    ?.querySelector<HTMLElement>('[aria-invalid="true"]')
                    ?.focus(),
            ),
    };
    if (editing.value) form.patch(`/requisites/${editing.value.id}`, options);
    else form.post('/requisites', options);
}
function toggle(item: Requisite) {
    busyId.value = item.id;
    operationError.value = '';
    router.patch(
        `/requisites/${item.id}`,
        { is_active: !item.is_active },
        {
            preserveScroll: true,
            onError: (errors) => {
                operationError.value = Object.values(errors).join(' ');
            },
            onFinish: () => {
                busyId.value = null;
            },
        },
    );
}
async function askDelete(item: Requisite) {
    deleting.value = item;
    await nextTick();
    deleteModal.value?.showModal();
}
function remove() {
    if (!deleting.value) return;
    busyId.value = deleting.value.id;
    operationError.value = '';
    router.delete(`/requisites/${deleting.value.id}`, {
        preserveScroll: true,
        onSuccess: () => deleteModal.value?.close(),
        onError: (errors) => {
            operationError.value = Object.values(errors).join(' ');
            deleteModal.value?.close();
        },
        onFinish: () => {
            busyId.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('Реквизиты', 'Payment details')" />
    <AppLayout :title="t('Реквизиты', 'Payment details')">
        <div class="requisites-space">
            <section class="glass-panel requisites-hero">
                <div>
                    <span class="glass-kicker">{{
                        t('КАРТЫ И КРИПТОВАЛЮТА', 'CARDS & CRYPTO')
                    }}</span>
                    <h1>{{ t('Ваши реквизиты.', 'Your payment details.') }}</h1>
                    <p class="glass-muted">
                        {{
                            t(
                                'Банки, сети и лимиты — всё в одном месте.',
                                'Banks, networks and limits, all in one place.',
                            )
                        }}
                    </p>
                </div>
                <button class="btn btn-primary" @click="openEditor()">
                    <Plus class="size-5" />{{
                        t('Добавить реквизиты', 'Add payment details')
                    }}
                </button>
            </section>

            <div class="method-shortcuts">
                <button
                    class="glass-surface method-shortcut"
                    @click="openEditor(null, 'card')"
                >
                    <span class="method-icon card-icon"><CreditCard /></span>
                    <span
                        ><strong>{{
                            t('Банковская карта', 'Bank card')
                        }}</strong
                        ><small class="glass-muted">{{
                            t(
                                'Выберите банк и валюту',
                                'Choose a bank and currency',
                            )
                        }}</small></span
                    >
                    <ArrowUpRight class="glass-muted ml-auto size-5" />
                </button>
                <button
                    class="glass-surface method-shortcut"
                    @click="openEditor(null, 'usdt')"
                >
                    <span class="method-icon crypto-icon"><Wallet /></span>
                    <span
                        ><strong>USDT</strong
                        ><small class="glass-muted"
                            >TRC-20 · ERC-20 · BEP-20</small
                        ></span
                    >
                    <ArrowUpRight class="glass-muted ml-auto size-5" />
                </button>
            </div>

            <p class="setup-note glass-muted">
                <ShieldCheck class="size-4 shrink-0" />{{
                    t(
                        'Сохранение реквизитов не запускает приём платежей.',
                        'Saving payment details does not start payment processing.',
                    )
                }}
            </p>
            <p v-if="operationError" class="text-error" role="alert">
                {{ operationError }}
            </p>

            <section
                class="glass-panel requisites-list"
                :aria-label="
                    t('Сохранённые реквизиты', 'Saved payment details')
                "
            >
                <div class="list-toolbar">
                    <h2>
                        {{ t('Мои реквизиты', 'My payment details') }}
                        <span class="glass-muted">{{ requisites.total }}</span>
                    </h2>
                    <div
                        class="filter-switch"
                        :aria-label="t('Фильтр', 'Filter')"
                        role="group"
                    >
                        <button
                            v-for="option in [
                                { value: 'all', label: t('Все', 'All') },
                                { value: 'card', label: t('Карты', 'Cards') },
                                { value: 'usdt', label: 'USDT' },
                            ]"
                            :key="option.value"
                            class="btn btn-sm"
                            :class="
                                filter === option.value
                                    ? 'btn-primary'
                                    : 'btn-ghost'
                            "
                            :aria-pressed="filter === option.value"
                            @click="changeFilter(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div v-if="!items.length" class="requisites-empty">
                    <span class="method-icon empty-icon"
                        ><CreditCard class="size-7"
                    /></span>
                    <h3>
                        {{
                            filter !== 'all'
                                ? t(
                                      'Таких реквизитов пока нет',
                                      'No matching payment details yet',
                                  )
                                : t(
                                      'Добавьте первые реквизиты',
                                      'Add your first payment details',
                                  )
                        }}
                    </h3>
                    <p class="glass-muted">
                        {{
                            t(
                                'Карта с выбором банка или USDT с выбором сети.',
                                'A card with your bank, or USDT on your chosen network.',
                            )
                        }}
                    </p>
                    <button
                        class="btn mt-5 btn-primary"
                        @click="
                            openEditor(
                                null,
                                filter === 'usdt' ? 'usdt' : 'card',
                            )
                        "
                    >
                        <Plus class="size-4" />{{
                            t('Добавить', 'Add details')
                        }}
                    </button>
                </div>

                <div v-else class="requisite-grid">
                    <article
                        v-for="item in items"
                        :key="item.id"
                        class="glass-surface requisite-card"
                        :data-testid="`requisite-${item.id}`"
                    >
                        <div class="requisite-top">
                            <span
                                class="method-icon"
                                :class="
                                    item.method === 'card'
                                        ? 'card-icon'
                                        : 'crypto-icon'
                                "
                                ><CreditCard
                                    v-if="item.method === 'card'" /><Wallet
                                    v-else
                            /></span>
                            <span
                                class="status-pill"
                                :class="{ 'is-active': item.is_active }"
                                ><span />{{
                                    item.is_active
                                        ? t('Включён', 'Enabled')
                                        : t('Отключён', 'Disabled')
                                }}</span
                            >
                        </div>
                        <div class="requisite-heading">
                            <h3>{{ item.label }}</h3>
                            <p class="glass-muted">
                                {{
                                    item.method === 'card'
                                        ? bankLabel(item)
                                        : networkLabel(item.network)
                                }}
                                · {{ item.currency }}
                            </p>
                        </div>
                        <p class="requisite-number">
                            {{
                                item.method === 'card'
                                    ? item.number_masked
                                    : item.address
                            }}
                        </p>
                        <p
                            v-if="item.method === 'card'"
                            class="glass-muted holder"
                        >
                            {{ item.holder }}
                        </p>
                        <dl class="requisite-limits">
                            <div>
                                <dt>{{ t('За платёж', 'Per payment') }}</dt>
                                <dd>
                                    {{ prettyAmount(item.min_amount) }} —
                                    {{ prettyAmount(item.max_amount) }}
                                    <span>{{ item.currency }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt>
                                    {{
                                        t(
                                            'Лимит ликвидности',
                                            'Liquidity limit',
                                        )
                                    }}
                                </dt>
                                <dd>
                                    {{
                                        item.liquidity_limit === null
                                            ? t('Без лимита', 'No limit')
                                            : prettyAmount(
                                                  item.liquidity_limit,
                                              ) +
                                              ' ' +
                                              item.currency
                                    }}
                                </dd>
                            </div>
                        </dl>
                        <div class="requisite-actions">
                            <button
                                class="btn btn-ghost btn-sm"
                                :disabled="busyId !== null"
                                @click="openEditor(item)"
                            >
                                <Pencil class="size-4" />{{
                                    t('Изменить', 'Edit')
                                }}
                            </button>
                            <button
                                class="btn ml-auto btn-ghost btn-sm"
                                :disabled="busyId !== null"
                                @click="toggle(item)"
                            >
                                {{
                                    item.is_active
                                        ? t('Отключить', 'Disable')
                                        : t('Включить', 'Enable')
                                }}
                            </button>
                            <button
                                class="btn btn-square btn-ghost btn-sm"
                                :aria-label="
                                    t('Удалить ', 'Delete ') + item.label
                                "
                                :disabled="busyId !== null"
                                @click="askDelete(item)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </article>
                </div>
                <Pagination
                    v-if="requisites.total > 20"
                    :links="requisites.links"
                />
            </section>
        </div>

        <dialog
            ref="modal"
            class="requisite-dialog glass-panel"
            aria-labelledby="requisite-editor-title"
            @cancel.prevent="closeEditor"
            @close="form.reset()"
        >
            <form class="editor-form" autocomplete="off" @submit.prevent="save">
                <div class="editor-heading">
                    <div>
                        <span class="glass-kicker">{{
                            t('РЕКВИЗИТЫ', 'PAYMENT DETAILS')
                        }}</span>
                        <h2 id="requisite-editor-title">
                            {{
                                editing
                                    ? t('Редактирование', 'Edit details')
                                    : t(
                                          'Новые реквизиты',
                                          'New payment details',
                                      )
                            }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="btn btn-square btn-ghost btn-sm"
                        :aria-label="t('Закрыть', 'Close')"
                        :disabled="form.processing"
                        @click="closeEditor"
                    >
                        <X class="size-5" />
                    </button>
                </div>
                <fieldset :disabled="form.processing" class="editor-fields">
                    <div
                        class="method-picker"
                        role="group"
                        :aria-label="t('Способ оплаты', 'Payment method')"
                    >
                        <button
                            type="button"
                            :aria-pressed="form.method === 'card'"
                            :class="{ selected: form.method === 'card' }"
                            @click="changeMethod('card')"
                        >
                            <CreditCard class="size-5" />{{ t('Карта', 'Card')
                            }}<Check
                                v-if="form.method === 'card'"
                                class="ml-auto size-4"
                            />
                        </button>
                        <button
                            type="button"
                            :aria-pressed="form.method === 'usdt'"
                            :class="{ selected: form.method === 'usdt' }"
                            @click="changeMethod('usdt')"
                        >
                            <Wallet class="size-5" />USDT<Check
                                v-if="form.method === 'usdt'"
                                class="ml-auto size-4"
                            />
                        </button>
                    </div>
                    <p
                        v-if="form.errors.method"
                        class="field-error"
                        role="alert"
                    >
                        {{ form.errors.method }}
                    </p>
                    <div class="field">
                        <label for="requisite-label">{{
                            t('Метка', 'Label')
                        }}</label
                        ><input
                            id="requisite-label"
                            v-model="form.label"
                            class="input"
                            required
                            maxlength="80"
                            :placeholder="
                                form.method === 'card'
                                    ? t(
                                          'Например, основная карта',
                                          'For example, main card',
                                      )
                                    : t(
                                          'Например, Trust Wallet',
                                          'For example, Trust Wallet',
                                      )
                            "
                            :aria-invalid="!!form.errors.label"
                            aria-describedby="error-label"
                        />
                        <p
                            v-if="form.errors.label"
                            id="error-label"
                            class="field-error"
                            role="alert"
                        >
                            {{ form.errors.label }}
                        </p>
                    </div>

                    <template v-if="form.method === 'card'">
                        <div class="field-pair">
                            <div class="field">
                                <label for="requisite-currency">{{
                                    t('Валюта', 'Currency')
                                }}</label
                                ><select
                                    id="requisite-currency"
                                    v-model="form.currency"
                                    class="select"
                                    required
                                    :aria-invalid="!!form.errors.currency"
                                    aria-describedby="error-currency"
                                >
                                    <option
                                        v-for="currency in currencies"
                                        :key="currency.value"
                                        :value="currency.value"
                                    >
                                        {{ currency.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.currency"
                                    id="error-currency"
                                    class="field-error"
                                    role="alert"
                                >
                                    {{ form.errors.currency }}
                                </p>
                            </div>
                            <div class="field">
                                <label for="requisite-bank">{{
                                    t('Банк', 'Bank')
                                }}</label
                                ><select
                                    id="requisite-bank"
                                    v-model="form.bank"
                                    class="select"
                                    required
                                    :aria-invalid="!!form.errors.bank"
                                    aria-describedby="error-bank"
                                >
                                    <option value="" disabled>
                                        {{
                                            t('Выберите банк', 'Choose a bank')
                                        }}
                                    </option>
                                    <option
                                        v-for="bank in banks"
                                        :key="bank.value"
                                        :value="bank.value"
                                    >
                                        {{
                                            bank.value === 'other'
                                                ? t('Другой банк', 'Other bank')
                                                : bank.label
                                        }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.bank"
                                    id="error-bank"
                                    class="field-error"
                                    role="alert"
                                >
                                    {{ form.errors.bank }}
                                </p>
                            </div>
                        </div>
                        <div v-if="form.bank === 'other'" class="field">
                            <label for="requisite-bank-name">{{
                                t('Название банка', 'Bank name')
                            }}</label
                            ><input
                                id="requisite-bank-name"
                                v-model="form.bank_name"
                                class="input"
                                required
                                maxlength="100"
                                :aria-invalid="!!form.errors.bank_name"
                                aria-describedby="error-bank-name"
                            />
                            <p
                                v-if="form.errors.bank_name"
                                id="error-bank-name"
                                class="field-error"
                                role="alert"
                            >
                                {{ form.errors.bank_name }}
                            </p>
                        </div>
                        <div class="field">
                            <label for="requisite-number">{{
                                t('Номер карты', 'Card number')
                            }}</label
                            ><input
                                id="requisite-number"
                                v-model="form.number"
                                class="number-input input"
                                inputmode="numeric"
                                autocomplete="off"
                                maxlength="25"
                                :required="
                                    !editing || editing.method !== 'card'
                                "
                                :placeholder="
                                    editing?.method === 'card'
                                        ? (editing.number_masked ?? '')
                                        : '0000 0000 0000 0000'
                                "
                                :aria-invalid="!!form.errors.number"
                                aria-describedby="number-hint error-number"
                            /><small id="number-hint" class="glass-muted">{{
                                editing?.method === 'card'
                                    ? t(
                                          'Оставьте пустым, чтобы сохранить текущий номер.',
                                          'Leave blank to keep the current number.',
                                      )
                                    : t(
                                          'Только номер карты. CVV и срок действия не нужны.',
                                          'Card number only. No CVV or expiry date needed.',
                                      )
                            }}</small>
                            <p
                                v-if="form.errors.number"
                                id="error-number"
                                class="field-error"
                                role="alert"
                            >
                                {{ form.errors.number }}
                            </p>
                        </div>
                        <div class="field">
                            <label for="requisite-holder">{{
                                t('Держатель карты', 'Cardholder')
                            }}</label
                            ><input
                                id="requisite-holder"
                                v-model="form.holder"
                                class="input"
                                required
                                maxlength="120"
                                :placeholder="t('Имя и фамилия', 'Full name')"
                                :aria-invalid="!!form.errors.holder"
                                aria-describedby="error-holder"
                            />
                            <p
                                v-if="form.errors.holder"
                                id="error-holder"
                                class="field-error"
                                role="alert"
                            >
                                {{ form.errors.holder }}
                            </p>
                        </div>
                    </template>
                    <template v-else>
                        <div class="field">
                            <label for="requisite-network">{{
                                t('Сеть USDT', 'USDT network')
                            }}</label
                            ><select
                                id="requisite-network"
                                v-model="form.network"
                                class="select"
                                required
                                :aria-invalid="!!form.errors.network"
                                aria-describedby="error-network"
                            >
                                <option
                                    v-for="network in networks"
                                    :key="network.value"
                                    :value="network.value"
                                >
                                    {{ network.label }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.network"
                                id="error-network"
                                class="field-error"
                                role="alert"
                            >
                                {{ form.errors.network }}
                            </p>
                        </div>
                        <div class="field">
                            <label for="requisite-address">{{
                                t('Адрес кошелька', 'Wallet address')
                            }}</label
                            ><input
                                id="requisite-address"
                                v-model="form.address"
                                class="number-input input"
                                required
                                maxlength="64"
                                spellcheck="false"
                                autocapitalize="off"
                                :placeholder="
                                    form.network === 'tron' ? 'T…' : '0x…'
                                "
                                :aria-invalid="!!form.errors.address"
                                aria-describedby="address-hint error-address"
                            /><small id="address-hint" class="glass-muted">{{
                                t(
                                    'Адрес должен соответствовать выбранной сети.',
                                    'The address must match the selected network.',
                                )
                            }}</small>
                            <p
                                v-if="form.errors.address"
                                id="error-address"
                                class="field-error"
                                role="alert"
                            >
                                {{ form.errors.address }}
                            </p>
                        </div>
                    </template>

                    <div class="limits-heading">
                        <Landmark class="size-4" /><span>{{
                            t('Лимиты', 'Limits')
                        }}</span
                        ><span class="glass-muted ml-auto">{{
                            form.currency
                        }}</span>
                    </div>
                    <div class="field-pair">
                        <div
                            v-for="amount in amountFields"
                            :key="amount.key"
                            class="field"
                            :class="{
                                'full-width': amount.key === 'liquidity_limit',
                            }"
                        >
                            <label :for="`requisite-${amount.key}`">{{
                                amount.label
                            }}</label
                            ><input
                                :id="`requisite-${amount.key}`"
                                v-model="form[amount.key]"
                                class="input"
                                inputmode="decimal"
                                :required="amount.required"
                                maxlength="20"
                                :placeholder="amount.placeholder"
                                :aria-invalid="!!form.errors[amount.key]"
                                :aria-describedby="`error-${amount.key}`"
                            />
                            <p
                                v-if="form.errors[amount.key]"
                                :id="`error-${amount.key}`"
                                class="field-error"
                                role="alert"
                            >
                                {{ form.errors[amount.key] }}
                            </p>
                        </div>
                    </div>
                    <label class="activation-row glass-surface"
                        ><input
                            v-model="form.is_active"
                            type="checkbox"
                            class="toggle toggle-primary toggle-sm"
                        /><span
                            ><strong>{{
                                t('Включить реквизиты', 'Enable these details')
                            }}</strong
                            ><small class="glass-muted">{{
                                t(
                                    'Готовы к использованию после подключения приёма платежей.',
                                    'Ready to use once payment processing is connected.',
                                )
                            }}</small></span
                        ></label
                    >
                    <p
                        v-if="form.errors.is_active"
                        class="field-error"
                        role="alert"
                    >
                        {{ form.errors.is_active }}
                    </p>
                </fieldset>
                <div class="editor-footer">
                    <button
                        type="button"
                        class="btn btn-ghost"
                        :disabled="form.processing"
                        @click="closeEditor"
                    >
                        {{ t('Отмена', 'Cancel') }}</button
                    ><button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span
                            v-if="form.processing"
                            class="loading loading-xs loading-spinner"
                        /><Check v-else class="size-4" />{{
                            form.processing
                                ? t('Сохранение…', 'Saving…')
                                : t('Сохранить', 'Save')
                        }}
                    </button>
                </div>
            </form>
        </dialog>

        <dialog
            ref="deleteModal"
            class="requisite-dialog delete-dialog glass-panel"
            aria-labelledby="delete-title"
            @cancel="busyId !== null && $event.preventDefault()"
        >
            <h2 id="delete-title">
                {{ t('Удалить реквизиты?', 'Delete these details?') }}
            </h2>
            <p class="glass-muted mt-3 break-words">{{ deleting?.label }}</p>
            <p class="glass-muted mt-2">
                {{
                    t(
                        'Реквизиты будут удалены из вашего списка.',
                        'These details will be removed from your list.',
                    )
                }}
            </p>
            <div class="editor-footer mt-6">
                <button
                    class="btn btn-ghost"
                    :disabled="busyId !== null"
                    @click="deleteModal?.close()"
                >
                    {{ t('Отмена', 'Cancel') }}</button
                ><button
                    class="btn btn-error"
                    :disabled="busyId !== null"
                    @click="remove"
                >
                    {{ t('Удалить', 'Delete') }}
                </button>
            </div>
        </dialog>
    </AppLayout>
</template>

<style scoped>
.requisites-space {
    display: grid;
    gap: 24px;
    max-width: 1360px;
    margin: auto;
}
.requisites-hero {
    display: flex;
    gap: 28px;
    align-items: center;
    justify-content: space-between;
    padding: 34px;
}
.requisites-hero h1 {
    font-size: clamp(1.8rem, 3vw, 2.8rem);
    font-weight: 650;
    letter-spacing: -0.045em;
    margin: 10px 0;
}
.method-shortcuts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}
.method-shortcut {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 22px;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s;
}
.method-shortcut:hover {
    border-color: var(--color-primary);
}
.method-shortcut strong,
.method-shortcut small {
    display: block;
}
.method-shortcut small {
    margin-top: 5px;
}
.method-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    border-radius: 16px;
    flex-shrink: 0;
}
.card-icon {
    background: color-mix(in srgb, var(--color-primary) 14%, transparent);
    color: var(--color-primary);
}
.crypto-icon {
    background: color-mix(in srgb, var(--color-success) 14%, transparent);
    color: var(--color-success);
}
.setup-note {
    display: flex;
    gap: 9px;
    align-items: center;
    font-size: 13px;
    padding: 0 5px;
}
.requisites-list {
    padding: 26px;
}
.list-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}
.list-toolbar h2 {
    font-size: 18px;
    font-weight: 600;
}
.list-toolbar h2 span {
    margin-left: 8px;
    font-weight: 400;
}
.filter-switch {
    display: flex;
    gap: 3px;
    background: var(--glass-input);
    padding: 4px;
    border-radius: 999px;
}
.requisites-empty {
    text-align: center;
    padding: 42px 12px 50px;
}
.empty-icon {
    background: var(--glass-surface);
    color: var(--glass-muted);
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
}
.requisites-empty h3 {
    font-size: 21px;
    font-weight: 600;
    margin-bottom: 9px;
}
.requisites-empty p {
    font-size: 14px;
}
.requisite-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}
.requisite-card {
    min-width: 0;
    padding: 24px;
    display: flex;
    flex-direction: column;
}
.requisite-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--glass-muted);
    border: 1px solid var(--glass-edge);
    border-radius: 99px;
    padding: 5px 9px;
}
.status-pill > span {
    width: 5px;
    height: 5px;
    background: currentColor;
    border-radius: 50%;
}
.is-active {
    color: var(--color-success);
}
.requisite-heading {
    margin: 22px 0 16px;
}
.requisite-heading h3 {
    font-weight: 600;
    font-size: 19px;
    overflow-wrap: anywhere;
}
.requisite-heading p {
    margin-top: 5px;
    font-size: 13px;
}
.requisite-number {
    font-family: ui-monospace, monospace;
    font-size: 17px;
    overflow-wrap: anywhere;
}
.holder {
    font-size: 12px;
    margin-top: 7px;
    overflow-wrap: anywhere;
}
.requisite-limits {
    display: grid;
    gap: 12px;
    border-top: 1px solid var(--glass-edge);
    margin-top: 22px;
    padding-top: 18px;
}
.requisite-limits dt {
    font-size: 12px;
    color: var(--glass-muted);
    margin-bottom: 4px;
}
.requisite-limits dd {
    font-size: 14px;
    overflow-wrap: anywhere;
}
.requisite-limits dd span {
    color: var(--glass-muted);
}
.requisite-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    padding-top: 22px;
    margin-top: auto;
}
.requisite-dialog {
    color: var(--color-base-content);
    position: fixed;
    margin: auto;
    padding: 0;
    width: min(580px, calc(100vw - 24px));
    max-height: calc(100dvh - 32px);
    overflow-y: auto;
    background-color: var(--color-base-200);
    overscroll-behavior: contain;
}
.requisite-dialog::backdrop {
    background: rgba(2, 8, 20, 0.6);
    backdrop-filter: blur(8px);
}
.editor-form {
    padding: 28px;
}
.editor-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.editor-heading h2,
.delete-dialog h2 {
    font-size: 24px;
    font-weight: 600;
    margin-top: 5px;
    letter-spacing: -0.03em;
}
.editor-fields {
    display: grid;
    gap: 19px;
    min-width: 0;
}
.method-picker {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.method-picker button {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 15px;
    border: 1px solid var(--glass-edge);
    border-radius: 16px;
    cursor: pointer;
}
.method-picker button.selected {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 10%, transparent);
    color: var(--color-primary);
}
.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}
.field label {
    font-size: 13px;
    font-weight: 550;
}
.field .input,
.field .select {
    width: 100%;
    min-width: 0;
    height: 48px;
    font-size: 16px;
}
.field small {
    font-size: 12px;
    line-height: 1.5;
}
.field [aria-invalid='true'] {
    border-color: var(--color-error);
}
.field-error {
    color: var(--color-error);
    font-size: 12px;
    line-height: 1.5;
}
.field-pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.full-width {
    grid-column: 1 / -1;
}
.number-input {
    font-family: ui-monospace, monospace;
}
.limits-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 19px;
    border-top: 1px solid var(--glass-edge);
    font-size: 13px;
}
.activation-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    cursor: pointer;
}
.activation-row strong {
    display: block;
    font-size: 13px;
    font-weight: 550;
}
.activation-row small {
    display: block;
    font-size: 11px;
    margin-top: 4px;
    line-height: 1.5;
}
.editor-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 24px;
}
.delete-dialog {
    padding: 28px;
    max-width: 430px;
}
@media (max-width: 767px) {
    .requisites-space {
        gap: 16px;
    }
    .requisites-hero {
        align-items: flex-start;
        flex-direction: column;
        padding: 24px;
        gap: 20px;
    }
    .requisites-hero .btn {
        width: 100%;
    }
    .requisites-hero p {
        font-size: 14px;
    }
    .method-shortcuts {
        gap: 10px;
    }
    .method-shortcut {
        padding: 17px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .method-shortcut > span:nth-child(2) {
        order: 3;
        flex-basis: 100%;
    }
    .method-shortcut strong {
        font-size: 14px;
    }
    .method-shortcut small {
        font-size: 11px;
    }
    .method-shortcut > svg {
        margin-left: auto;
    }
    .requisites-list {
        padding: 18px;
    }
    .list-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-switch {
        align-self: flex-start;
    }
    .requisite-grid {
        grid-template-columns: 1fr;
    }
    .requisite-card {
        padding: 20px;
    }
    .requisites-empty {
        padding: 28px 4px;
    }
    .requisites-empty h3 {
        font-size: 18px;
    }
    .editor-form {
        padding: 20px;
    }
    .field-pair {
        gap: 14px;
    }
    .editor-footer .btn {
        flex: 1;
    }
    .requisite-dialog {
        border-radius: 24px;
    }
    .editor-heading h2 {
        font-size: 22px;
    }
    .method-picker button {
        padding: 13px;
    }
    .requisite-actions .btn {
        padding-inline: 10px;
    }
}
@media (prefers-reduced-transparency: reduce) {
    .requisite-dialog::backdrop {
        backdrop-filter: none;
    }
}
</style>
