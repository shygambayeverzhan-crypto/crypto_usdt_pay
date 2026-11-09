<script setup lang="ts">
import FlagIcon from '@/components/ui/FlagIcon.vue';
import { dashboard, login, register } from '@/routes';
import { vueLang } from '@erag/lang-sync-inertia';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    ChevronDown,
    FileText,
    FlaskConical,
    Layers3,
    LockKeyhole,
    Wallet,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

withDefaults(defineProps<{ canRegister: boolean }>(), { canRegister: true });
type LocaleOption = { code: string; label: string; flag: string };
const page = usePage();
const { __ } = vueLang();
const appName = computed(() => {
    const name = String(page.props.name ?? '').trim();
    return !name || name.toLowerCase() === 'laravel' ? 'USDT PAY' : name;
});
const localeMenuOpen = ref(false);
const fallbackLocales: LocaleOption[] = [
    { code: 'ru', label: 'Русский', flag: 'RU' },
    { code: 'en', label: 'English', flag: 'US' },
];
const sharedLocales = computed(
    () =>
        page.props.locales as
            | { available?: LocaleOption[]; enabled?: string[] }
            | undefined,
);
const availableLocales = computed<LocaleOption[]>(() => {
    const available = sharedLocales.value?.available ?? [];
    if (!available.length) return fallbackLocales;
    const enabled = sharedLocales.value?.enabled ?? [];
    const filtered = enabled.length
        ? available.filter((item) => enabled.includes(item.code))
        : available;
    return filtered.length ? filtered : available;
});
const currentLocale = computed(() =>
    String(page.props.locale || availableLocales.value[0]?.code || 'ru'),
);
const currentLocaleOption = computed(
    () =>
        availableLocales.value.find(
            (locale) => locale.code === currentLocale.value,
        ) ??
        availableLocales.value[0] ??
        fallbackLocales[0],
);
const copy = computed(() =>
    currentLocale.value === 'en'
        ? {
              subtitle: 'Your payment workspace',
              eyebrow: 'USDT · TRON NETWORK',
              title: 'Crypto payments.',
              titleAccent: 'Perfectly clear.',
              description:
                  'Invoices, wallet addresses and your business. A thoughtful workspace with everything in its place.',
              note: 'Explore the test panel. Real payments are disabled.',
              preview: 'YOUR WORKSPACE',
              cardTitle: 'Everything in view.',
              network: 'TRON network · TRC-20',
              test: 'Test mode',
              invoice: 'Invoices',
              invoiceDetail: 'Every invoice and its status',
              addresses: 'Wallet addresses',
              addressesDetail: 'Your receiving addresses',
              accounts: 'Your own space',
              accountsDetail: 'Separate seller accounts',
              featureOne: 'Clarity in every detail',
              featureOneText:
                  'Your invoices and their status, together in one dashboard.',
              featureTwo: 'A space for your business',
              featureTwoText:
                  'A personal seller account, with access to your own data.',
              featureThree: 'Always within reach',
              featureThreeText:
                  'The same familiar workspace on your phone and computer.',
              footer: 'Made for a clearer payment experience.',
              language: 'Choose language',
          }
        : {
              subtitle: 'Ваш платёжный кабинет',
              eyebrow: 'USDT · СЕТЬ TRON',
              title: 'Криптоплатежи.',
              titleAccent: 'Предельно ясно.',
              description:
                  'Инвойсы, адреса и ваш бизнес. Продуманный кабинет, где всё на своём месте.',
              note: 'Знакомьтесь с тестовой панелью. Реальные платежи отключены.',
              preview: 'ВАШЕ ПРОСТРАНСТВО',
              cardTitle: 'Всё перед глазами.',
              network: 'Сеть TRON · TRC-20',
              test: 'Тестовый режим',
              invoice: 'Инвойсы',
              invoiceDetail: 'Каждый счёт и его статус',
              addresses: 'Адреса кошельков',
              addressesDetail: 'Ваши адреса для приёма',
              accounts: 'Свой кабинет',
              accountsDetail: 'Отдельный доступ селлеров',
              featureOne: 'Ясность в деталях',
              featureOneText:
                  'Инвойсы и их статусы собраны в одной панели управления.',
              featureTwo: 'Пространство для бизнеса',
              featureTwoText:
                  'Личный кабинет селлера с доступом к своим данным.',
              featureThree: 'Всегда под рукой',
              featureThreeText:
                  'Привычный кабинет на большом экране и в вашем телефоне.',
              footer: 'Платёжный опыт, в котором всё понятно.',
              language: 'Выбрать язык',
          },
);
function switchLocale(code: string) {
    localeMenuOpen.value = false;
    if (
        code === currentLocale.value ||
        !availableLocales.value.some((locale) => locale.code === code)
    )
        return;
    router.get(
        `/lang/${code}`,
        {},
        { preserveScroll: true, preserveState: false },
    );
}
onMounted(() => document.documentElement.classList.add('page-welcome'));
onBeforeUnmount(() =>
    document.documentElement.classList.remove('page-welcome'),
);
</script>

<template>
    <Head :title="appName" />
    <main class="welcome-glass">
        <div class="welcome-atmosphere" aria-hidden="true">
            <div class="atmosphere-light atmosphere-light-blue"></div>
            <div class="atmosphere-light atmosphere-light-violet"></div>
            <div class="atmosphere-light atmosphere-light-ice"></div>
        </div>
        <header class="welcome-header">
            <Link href="/" class="welcome-brand">
                <span class="welcome-brand-icon"
                    ><Layers3 :size="22" :stroke-width="1.6"
                /></span>
                <span
                    ><strong>{{ appName }}</strong
                    ><small>{{ copy.subtitle }}</small></span
                >
            </Link>
            <div
                class="locale-switch"
                @keydown.esc="localeMenuOpen = false"
                @focusout="
                    (event) => {
                        if (
                            !(event.currentTarget as HTMLElement).contains(
                                event.relatedTarget as Node,
                            )
                        )
                            localeMenuOpen = false;
                    }
                "
            >
                <button
                    type="button"
                    class="locale-button"
                    :aria-label="copy.language"
                    :aria-expanded="localeMenuOpen"
                    aria-haspopup="menu"
                    @click="localeMenuOpen = !localeMenuOpen"
                >
                    <FlagIcon :code="currentLocaleOption.flag" size="S" /><span
                        class="locale-label"
                        >{{ currentLocaleOption.label }}</span
                    ><ChevronDown :size="14" aria-hidden="true" />
                </button>
                <div v-if="localeMenuOpen" class="locale-menu" role="menu">
                    <button
                        v-for="locale in availableLocales"
                        :key="locale.code"
                        type="button"
                        role="menuitem"
                        :class="{ selected: locale.code === currentLocale }"
                        @click="switchLocale(locale.code)"
                    >
                        <FlagIcon :code="locale.flag" size="S" /><span>{{
                            locale.label
                        }}</span>
                    </button>
                </div>
            </div>
        </header>
        <section class="welcome-hero">
            <div class="hero-copy">
                <p class="hero-eyebrow">
                    <span aria-hidden="true"></span>{{ copy.eyebrow }}
                </p>
                <h1>
                    {{ copy.title }}<span>{{ copy.titleAccent }}</span>
                </h1>
                <p class="hero-description">{{ copy.description }}</p>
                <div class="hero-actions">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="welcome-button welcome-button-primary"
                        >{{ __('frontend.welcome.go_dashboard')
                        }}<ArrowUpRight :size="19" aria-hidden="true"
                    /></Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="welcome-button welcome-button-primary"
                            >{{ __('frontend.welcome.login')
                            }}<ArrowUpRight :size="19" aria-hidden="true"
                        /></Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="welcome-button welcome-button-secondary"
                            >{{ __('frontend.welcome.register') }}</Link
                        >
                    </template>
                </div>
                <p class="hero-note">
                    <FlaskConical :size="16" aria-hidden="true" /><span>{{
                        copy.note
                    }}</span>
                </p>
            </div>
            <div class="workspace-preview">
                <div class="preview-orbit" aria-hidden="true"></div>
                <div class="preview-backplate" aria-hidden="true"></div>
                <div class="preview-card">
                    <div class="preview-heading">
                        <span>{{ copy.preview }}</span
                        ><span class="preview-heading-dots" aria-hidden="true"
                            >•••</span
                        >
                    </div>
                    <h2>{{ copy.cardTitle }}</h2>
                    <div class="asset-card">
                        <div class="asset-identity">
                            <span class="asset-icon" aria-hidden="true"
                                ><svg viewBox="0 0 48 48" fill="none">
                                    <path
                                        d="M12 11h24v6H27v19h-6V17h-9v-6Z"
                                        fill="currentColor"
                                    />
                                    <ellipse
                                        cx="24"
                                        cy="22"
                                        rx="16"
                                        ry="4"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    /></svg
                            ></span>
                            <div>
                                <strong>USDT</strong
                                ><span>{{ copy.network }}</span>
                            </div>
                        </div>
                        <ArrowDownLeft
                            class="asset-arrow"
                            :size="22"
                            :stroke-width="1.5"
                            aria-hidden="true"
                        />
                        <span class="asset-test"
                            ><span aria-hidden="true"></span
                            >{{ copy.test }}</span
                        >
                        <div class="asset-wave" aria-hidden="true"></div>
                    </div>
                    <div class="preview-row">
                        <span class="preview-row-icon"
                            ><FileText
                                :size="19"
                                :stroke-width="1.6"
                                aria-hidden="true"
                        /></span>
                        <div>
                            <strong>{{ copy.invoice }}</strong
                            ><small>{{ copy.invoiceDetail }}</small>
                        </div>
                        <span
                            class="preview-row-line"
                            aria-hidden="true"
                        ></span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-row-icon"
                            ><Wallet
                                :size="19"
                                :stroke-width="1.6"
                                aria-hidden="true"
                        /></span>
                        <div>
                            <strong>{{ copy.addresses }}</strong
                            ><small>{{ copy.addressesDetail }}</small>
                        </div>
                        <span
                            class="preview-row-line"
                            aria-hidden="true"
                        ></span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-row-icon"
                            ><LockKeyhole
                                :size="19"
                                :stroke-width="1.6"
                                aria-hidden="true"
                        /></span>
                        <div>
                            <strong>{{ copy.accounts }}</strong
                            ><small>{{ copy.accountsDetail }}</small>
                        </div>
                        <span
                            class="preview-row-line"
                            aria-hidden="true"
                        ></span>
                    </div>
                </div>
            </div>
        </section>
        <section class="welcome-features">
            <article>
                <span class="feature-index">01</span>
                <h2>{{ copy.featureOne }}</h2>
                <p>{{ copy.featureOneText }}</p>
            </article>
            <article>
                <span class="feature-index">02</span>
                <h2>{{ copy.featureTwo }}</h2>
                <p>{{ copy.featureTwoText }}</p>
            </article>
            <article>
                <span class="feature-index">03</span>
                <h2>{{ copy.featureThree }}</h2>
                <p>{{ copy.featureThreeText }}</p>
            </article>
        </section>
        <footer class="welcome-footer">
            <span>{{ appName }}</span>
            <p>{{ copy.footer }}</p>
            <span>USDT · TRC-20</span>
        </footer>
    </main>
</template>

<style scoped>
.welcome-glass {
    position: relative;
    isolation: isolate;
    min-height: 100svh;
    overflow: hidden;
    background: #07111f;
    color: #eef5ff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    -webkit-font-smoothing: antialiased;
}
.welcome-atmosphere {
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: -1;
    overflow: hidden;
    background: radial-gradient(ellipse at 70% 42%, #122b49 0, transparent 52%);
}
.atmosphere-light {
    position: absolute;
    border-radius: 50%;
    filter: blur(95px);
}
.atmosphere-light-blue {
    top: 3%;
    right: 5%;
    width: 440px;
    height: 480px;
    background: #345eab33;
}
.atmosphere-light-violet {
    top: 35%;
    right: -180px;
    width: 460px;
    height: 380px;
    background: #7d61b833;
}
.atmosphere-light-ice {
    top: 48%;
    left: -180px;
    width: 420px;
    height: 320px;
    background: #53cbdd0f;
}
.welcome-header,
.welcome-hero,
.welcome-features,
.welcome-footer {
    width: min(1200px, calc(100% - 96px));
    margin-inline: auto;
}
.welcome-header {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-block: 30px;
}
.welcome-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    border-radius: 16px;
}
.welcome-brand-icon {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    border: 1px solid #d4e9ff33;
    border-radius: 16px;
    background: linear-gradient(145deg, #c2e6ff33, #a5cfff0c);
    box-shadow:
        inset 0 1px 0 #fff3,
        0 8px 28px #0002;
    color: #c6e6ff;
}
.welcome-brand strong {
    display: block;
    font-size: 14px;
    font-weight: 650;
    letter-spacing: 0.12em;
}
.welcome-brand small {
    display: block;
    margin-top: 3px;
    color: #899db6;
    font-size: 11px;
}
.locale-switch {
    position: relative;
}
.locale-button {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 42px;
    padding: 9px 14px;
    border: 1px solid #e0eeff21;
    border-radius: 999px;
    background: #d8eaff09;
    color: #dce9f8;
    font-size: 12px;
    cursor: pointer;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}
.locale-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 10px);
    width: 180px;
    border: 1px solid #c3ddff30;
    border-radius: 18px;
    padding: 6px;
    background: #152338f2;
    box-shadow: 0 15px 40px #0005;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
}
.locale-menu button {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 12px;
    padding: 11px;
    border-radius: 12px;
    color: #dce9f8;
    text-align: left;
    font-size: 13px;
    cursor: pointer;
}
.locale-menu button:hover,
.locale-menu button.selected {
    background: #a5cfff18;
}
.welcome-hero {
    display: grid;
    grid-template-columns: 1.14fr 1fr;
    align-items: center;
    gap: 70px;
    padding-block: 85px 100px;
}
.hero-eyebrow {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0 0 30px;
    color: #a9cbe9;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.17em;
}
.hero-eyebrow span {
    width: 6px;
    height: 6px;
    background: #b4e5ff;
    border-radius: 50%;
    box-shadow: 0 0 16px #7cd8ff7f;
}
.hero-copy h1 {
    margin: 0;
    font-size: clamp(38px, 4.7vw, 64px);
    font-weight: 570;
    line-height: 1.12;
    letter-spacing: -0.055em;
}
.hero-copy h1 span {
    display: block;
    margin-top: 8px;
    background: linear-gradient(115deg, #def5ff 6%, #9fc7ff 58%, #c1b7e9 100%);
    color: transparent;
    background-clip: text;
    -webkit-background-clip: text;
}
.hero-description {
    max-width: 420px;
    margin: 26px 0 0;
    color: #a0b1c8;
    font-size: 16px;
    font-weight: 400;
    line-height: 1.85;
}
.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 35px;
}
.welcome-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 22px;
    min-height: 50px;
    padding: 13px 24px;
    border: 1px solid transparent;
    border-radius: 18px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition:
        transform 180ms ease,
        background 180ms ease,
        box-shadow 180ms ease;
}
.welcome-button-primary {
    background: linear-gradient(135deg, #dbf3ff, #a3cbff);
    border-color: #e4f5ff80;
    box-shadow:
        inset 0 1px 0 #fff9,
        0 8px 30px #74aaff20;
    color: #102840;
}
.welcome-button-primary:hover {
    box-shadow:
        inset 0 1px 0 #fff9,
        0 9px 34px #74aaff40;
    transform: translateY(-2px);
}
.welcome-button-secondary {
    background: linear-gradient(135deg, #d8eaff12, #d8eaff06);
    border-color: #d2e5ff2e;
    box-shadow: inset 0 1px 0 #fff1;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    color: #d9e7f7;
}
.welcome-button-secondary:hover {
    background: #d8eaff19;
    transform: translateY(-2px);
}
.hero-note {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    max-width: 350px;
    margin-top: 22px;
    color: #7e94ad;
    font-size: 11px;
    line-height: 1.7;
}
.hero-note svg {
    flex: none;
    margin-top: 2px;
}
.workspace-preview {
    position: relative;
    min-width: 0;
    padding: 22px 8px 10px;
}
.preview-orbit {
    position: absolute;
    top: -40px;
    right: -32px;
    width: 270px;
    height: 270px;
    border-radius: 50%;
    border: 1px solid #c5e8ff21;
    background:
        radial-gradient(circle at 32% 24%, #d8f3ff33, transparent 50%),
        linear-gradient(130deg, #a4d7ff0c, #8379df15);
    box-shadow: inset 0 0 80px #a0c9ff0a;
    transform: rotate(-25deg);
}
.preview-backplate {
    position: absolute;
    inset: 12px 6px 12px 14px;
    border: 1px solid #c6dcff21;
    border-radius: 32px;
    background: linear-gradient(120deg, #bde3ff0a, #a28bd914);
    transform: rotate(7deg);
}
.preview-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #d8eaff40;
    border-radius: 28px;
    padding: 27px;
    background: linear-gradient(125deg, #d4e9ff1f, #8cb6e70d 55%, #ae94eb12);
    box-shadow:
        inset 0 1px 0 #fff3,
        inset 1px 0 0 #d8eaff14,
        0 30px 65px #02071345;
    backdrop-filter: blur(34px) saturate(125%);
    -webkit-backdrop-filter: blur(34px) saturate(125%);
}
.preview-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #9cb6d2;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.16em;
}
.preview-heading-dots {
    font-size: 15px;
    letter-spacing: 3px;
    line-height: 1;
}
.preview-card h2 {
    margin: 9px 0 23px;
    font-size: 24px;
    line-height: 1.3;
    font-weight: 500;
    letter-spacing: -0.04em;
}
.asset-card {
    position: relative;
    overflow: hidden;
    min-height: 145px;
    padding: 22px;
    margin-bottom: 14px;
    border: 1px solid #e3f3ff33;
    border-radius: 21px;
    background: linear-gradient(115deg, #c4e9ff1e, #8eb8f31b 50%, #b89ee526);
    box-shadow: inset 0 1px 0 #fff2;
}
.asset-identity {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 13px;
}
.asset-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border: 1px solid #d2f9ef35;
    border-radius: 50%;
    background: linear-gradient(140deg, #c6ffed2b, #bafae70b);
    color: #bff1e0;
}
.asset-icon svg {
    width: 28px;
    height: 28px;
}
.asset-identity strong {
    display: block;
    font-size: 21px;
    font-weight: 550;
    letter-spacing: -0.02em;
}
.asset-identity div > span {
    display: block;
    margin-top: 2px;
    font-size: 10px;
    color: #aec0d7;
}
.asset-arrow {
    position: absolute;
    right: 22px;
    top: 25px;
    color: #d2e5ff70;
}
.asset-test {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 22px;
    color: #b3c8df;
    font-size: 10px;
}
.asset-test > span {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #b5cce4;
}
.asset-wave {
    position: absolute;
    width: 200px;
    height: 200px;
    right: -50px;
    bottom: -140px;
    border: 25px solid #dae7ff09;
    border-radius: 50%;
    box-shadow:
        0 0 0 25px #c6dbff05,
        0 0 0 50px #c6dbff03;
}
.preview-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 3px;
    border-bottom: 1px solid #d5e7ff10;
}
.preview-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}
.preview-row-icon {
    display: grid;
    place-items: center;
    flex: none;
    width: 37px;
    height: 37px;
    border: 1px solid #deecff19;
    border-radius: 12px;
    background: #d5e7ff08;
    color: #b7d1ed;
}
.preview-row strong {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #e1ebf8;
}
.preview-row small {
    display: block;
    margin-top: 3px;
    font-size: 10px;
    color: #8fa7c0;
}
.preview-row-line {
    width: 18px;
    height: 3px;
    margin-left: auto;
    border-radius: 5px;
    background: #bbd4f125;
}
.welcome-features {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 44px;
    padding: 10px 0 54px;
}
.welcome-features article {
    border-top: 1px solid #c6ddff20;
    padding-top: 21px;
}
.feature-index {
    color: #6f88a3;
    font-size: 10px;
    letter-spacing: 0.08em;
}
.welcome-features h2 {
    margin: 14px 0 8px;
    font-size: 16px;
    font-weight: 500;
    letter-spacing: -0.02em;
}
.welcome-features p {
    max-width: 280px;
    margin: 0;
    color: #8299b2;
    font-size: 12px;
    line-height: 1.8;
}
.welcome-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding-block: 26px;
    border-top: 1px solid #bcd6fa12;
    color: #6e849e;
    font-size: 10px;
}
.welcome-footer > span:first-child {
    color: #91a7c0;
    font-weight: 600;
    letter-spacing: 0.12em;
}
.welcome-footer p {
    margin: 0;
}
.welcome-glass :is(a, button):focus-visible {
    outline: 2px solid #acd6ff;
    outline-offset: 5px;
}
@media (min-width: 1440px) {
    .welcome-hero {
        padding-block: 100px 120px;
    }
}
@media (max-width: 1023px) {
    .welcome-header,
    .welcome-hero,
    .welcome-features,
    .welcome-footer {
        width: calc(100% - 56px);
    }
    .welcome-hero {
        gap: 35px;
        padding-block: 65px 75px;
    }
    .hero-copy h1 {
        font-size: 43px;
    }
    .hero-description {
        font-size: 14px;
    }
    .preview-card {
        padding: 22px;
    }
    .asset-card {
        padding: 18px;
    }
    .asset-identity {
        gap: 9px;
    }
    .asset-arrow {
        display: none;
    }
    .welcome-button {
        padding-inline: 18px;
        gap: 14px;
    }
    .welcome-features {
        gap: 28px;
    }
}
@media (max-width: 760px) {
    .welcome-header,
    .welcome-hero,
    .welcome-features,
    .welcome-footer {
        width: calc(100% - 40px);
    }
    .welcome-header {
        padding-block: 21px;
    }
    .welcome-brand-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
    }
    .welcome-brand strong {
        font-size: 12px;
    }
    .welcome-brand small {
        font-size: 10px;
    }
    .locale-label {
        display: none;
    }
    .locale-button {
        gap: 7px;
        padding-inline: 11px;
    }
    .welcome-hero {
        grid-template-columns: 1fr;
        gap: 50px;
        padding-block: 48px 55px;
    }
    .hero-copy {
        max-width: 520px;
    }
    .hero-eyebrow {
        margin-bottom: 24px;
        font-size: 9px;
    }
    .hero-copy h1 {
        font-size: clamp(36px, 8.7vw, 53px);
    }
    .hero-description {
        max-width: 370px;
        margin-top: 22px;
        font-size: 15px;
        line-height: 1.75;
    }
    .hero-actions {
        margin-top: 28px;
    }
    .welcome-button {
        min-height: 50px;
        padding-inline: 23px;
    }
    .hero-note {
        font-size: 10px;
        max-width: 330px;
        margin-top: 19px;
    }
    .workspace-preview {
        width: min(100%, 430px);
        margin-inline: auto;
        padding: 12px 8px 10px;
    }
    .preview-card {
        padding: 24px;
        border-radius: 25px;
    }
    .preview-card h2 {
        font-size: 24px;
    }
    .asset-card {
        padding: 21px;
        min-height: 150px;
    }
    .asset-identity {
        gap: 13px;
    }
    .asset-arrow {
        display: block;
    }
    .preview-row {
        padding-block: 14px;
    }
    .preview-row strong {
        font-size: 12px;
    }
    .preview-row small {
        font-size: 11px;
    }
    .preview-orbit {
        width: 230px;
        height: 230px;
        right: -15px;
        top: -10px;
    }
    .welcome-features {
        grid-template-columns: 1fr;
        gap: 24px;
        padding-bottom: 40px;
    }
    .welcome-features article {
        display: grid;
        grid-template-columns: 24px 1fr;
        column-gap: 15px;
        padding-top: 22px;
    }
    .feature-index {
        grid-row: 1/3;
        padding-top: 3px;
    }
    .welcome-features h2 {
        margin: 0 0 7px;
        font-size: 16px;
    }
    .welcome-features p {
        max-width: 370px;
        font-size: 12px;
    }
    .welcome-footer {
        flex-wrap: wrap;
        gap: 12px;
        font-size: 9px;
        padding-block: 24px;
    }
    .welcome-footer p {
        order: 3;
        width: 100%;
    }
    .atmosphere-light-blue {
        right: -160px;
        top: 20%;
        width: 400px;
    }
    .atmosphere-light-violet {
        top: 40%;
    }
}
@media (max-width: 360px) {
    .welcome-header,
    .welcome-hero,
    .welcome-features,
    .welcome-footer {
        width: calc(100% - 32px);
    }
    .hero-copy h1 {
        font-size: 34px;
    }
    .hero-actions {
        gap: 9px;
    }
    .welcome-button {
        padding-inline: 16px;
        font-size: 12px;
    }
    .preview-card {
        padding: 19px;
    }
    .asset-card {
        padding: 18px;
    }
    .asset-arrow {
        display: none;
    }
}
@media (prefers-reduced-motion: reduce) {
    .welcome-button {
        transition: none;
    }
    .welcome-button:hover {
        transform: none;
    }
}
@supports not (backdrop-filter: blur(20px)) {
    .preview-card {
        background: #17283d;
    }
    .locale-menu {
        background: #152338;
    }
}
</style>
