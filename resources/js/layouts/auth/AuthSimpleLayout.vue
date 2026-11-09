<script setup lang="ts">
import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue';
import { vueLang } from '@erag/lang-sync-inertia';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();

const { __ } = vueLang();
</script>

<template>
    <div class="auth-scene">
        <div class="auth-aurora" aria-hidden="true" />

        <header class="auth-header">
            <Link href="/" class="auth-brand" aria-label="USDT PAY">
                <span
                    class="glass-brand-mark auth-brand-symbol"
                    aria-hidden="true"
                    >₮</span
                >
                <span class="auth-brand-name">USDT<span>PAY</span></span>
            </Link>
            <div class="auth-preferences">
                <LanguageSwitcher
                    :full-width="false"
                    direction="down"
                    align="end"
                />
                <ThemeSwitcher />
            </div>
        </header>

        <main class="auth-main">
            <section class="auth-intro">
                <div class="glass-kicker auth-network">
                    USDT <span /> TRON · TRC-20
                </div>
                <h2>
                    {{ __('frontend.welcome.hero_title') }}<br /><span>{{
                        __('frontend.welcome.hero_title_accent')
                    }}</span>
                </h2>
                <p class="glass-muted auth-intro-description">
                    {{ __('frontend.welcome.feature_dashboard_description') }}
                </p>

                <div class="auth-art" aria-hidden="true">
                    <div class="auth-orbit auth-orbit-one" />
                    <div class="auth-orbit auth-orbit-two" />
                    <div class="auth-glass-card auth-glass-card-back" />
                    <div class="auth-glass-card auth-glass-card-front">
                        <div class="auth-art-card-top">
                            <span>USDT PAY</span
                            ><ArrowUpRight :size="22" :stroke-width="1.5" />
                        </div>
                        <div class="auth-coin">₮</div>
                        <div class="auth-art-card-bottom">
                            <span>USDT</span><span>TRC-20</span>
                        </div>
                    </div>
                    <div class="glass-surface auth-art-chip">
                        TRON<span>↗</span>
                    </div>
                </div>
            </section>

            <section
                class="glass-panel auth-panel"
                aria-labelledby="auth-title"
            >
                <div class="auth-panel-heading">
                    <div
                        class="glass-surface auth-entry-mark"
                        aria-hidden="true"
                    >
                        <ArrowUpRight :size="24" :stroke-width="1.6" />
                    </div>
                    <h1 id="auth-title">{{ title }}</h1>
                    <p class="glass-muted">{{ description }}</p>
                </div>
                <slot />
            </section>
        </main>

        <footer class="auth-footer">
            <span>USDT PAY</span><span>USDT · TRON / TRC-20</span>
        </footer>
    </div>
</template>

<style scoped>
.auth-scene {
    position: relative;
    isolation: isolate;
    display: flex;
    min-height: 100svh;
    flex-direction: column;
    overflow: clip;
    padding: 28px clamp(24px, 5vw, 80px) 20px;
}

.auth-aurora {
    position: absolute;
    z-index: -1;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(
            ellipse at 24% 56%,
            rgb(72 159 212 / 13%),
            transparent 44%
        ),
        radial-gradient(
            ellipse at 88% 18%,
            rgb(132 124 230 / 12%),
            transparent 40%
        );
}

.auth-header,
.auth-footer {
    display: flex;
    width: 100%;
    max-width: 1200px;
    align-items: center;
    justify-content: space-between;
    margin-inline: auto;
}

.auth-header {
    position: relative;
    z-index: 2;
    gap: 12px;
}
.auth-brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}
.auth-brand-symbol {
    display: grid;
    width: 44px;
    height: 44px;
    place-items: center;
    border-radius: 15px;
    font-size: 25px;
}
.auth-brand-name {
    display: flex;
    align-items: baseline;
    gap: 5px;
    font-size: 17px;
    font-weight: 760;
    letter-spacing: -0.035em;
}
.auth-brand-name span {
    opacity: 0.54;
    font-weight: 460;
}
.auth-preferences {
    display: flex;
    align-items: center;
    gap: 8px;
}
.auth-main {
    display: grid;
    width: 100%;
    max-width: 1120px;
    grid-template-columns: minmax(0, 1fr) minmax(0, 440px);
    align-items: center;
    gap: clamp(48px, 8vw, 120px);
    flex: 1;
    margin: 52px auto;
}
.auth-intro {
    min-width: 0;
    padding-top: 10px;
}
.auth-network {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
    font-size: 11px;
}
.auth-network > span {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.5;
}
.auth-intro h2 {
    max-width: 530px;
    font-size: clamp(40px, 4.4vw, 62px);
    font-weight: 620;
    letter-spacing: -0.055em;
    line-height: 1.07;
}
.auth-intro h2 > span {
    color: color-mix(
        in oklab,
        var(--color-primary) 72%,
        var(--color-base-content)
    );
}
.auth-intro-description {
    max-width: 410px;
    margin-top: 25px;
    font-size: 15px;
    line-height: 1.8;
}

.auth-art {
    position: relative;
    width: 100%;
    height: 285px;
    margin-top: 30px;
}
.auth-orbit {
    position: absolute;
    top: 31px;
    left: 28px;
    width: 350px;
    height: 215px;
    border: 1px solid color-mix(in oklab, var(--color-primary) 14%, transparent);
    border-radius: 50%;
    transform: rotate(-23deg);
}
.auth-orbit-two {
    top: 3px;
    left: 89px;
    width: 226px;
    height: 266px;
    transform: rotate(51deg);
    opacity: 0.6;
}
.auth-glass-card {
    position: absolute;
    width: 270px;
    height: 168px;
    border: 1px solid
        color-mix(in oklab, var(--color-base-content) 22%, transparent);
    border-radius: 26px;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    box-shadow:
        inset 0 1px 0 rgb(255 255 255 / 16%),
        0 24px 50px rgb(2 9 22 / 15%);
}
.auth-glass-card-back {
    top: 21px;
    left: 52px;
    transform: rotate(-17deg);
    background: linear-gradient(
        135deg,
        rgb(145 155 255 / 15%),
        rgb(138 219 246 / 3%)
    );
}
.auth-glass-card-front {
    top: 60px;
    left: 95px;
    padding: 23px;
    transform: rotate(9deg);
    background: linear-gradient(
        135deg,
        rgb(193 232 255 / 21%),
        rgb(111 156 234 / 5%) 48%,
        rgb(166 155 243 / 15%)
    );
}
.auth-art-card-top,
.auth-art-card-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 10px;
    letter-spacing: 0.13em;
}
.auth-art-card-top {
    opacity: 0.75;
}
.auth-art-card-bottom {
    position: absolute;
    right: 23px;
    bottom: 22px;
    left: 23px;
}
.auth-art-card-bottom span:first-child {
    font-size: 17px;
    font-weight: 600;
    letter-spacing: -0.02em;
}
.auth-art-card-bottom span:last-child {
    opacity: 0.6;
}
.auth-coin {
    position: absolute;
    top: 38px;
    left: 109px;
    display: grid;
    width: 62px;
    height: 62px;
    place-items: center;
    border: 1px solid rgb(209 241 255 / 27%);
    border-radius: 50%;
    background: linear-gradient(
        140deg,
        rgb(183 227 255 / 24%),
        rgb(175 178 255 / 6%)
    );
    font-size: 37px;
    line-height: 1;
    text-shadow: 0 2px 20px rgb(158 224 255 / 28%);
    box-shadow: inset 0 1px 1px rgb(255 255 255 / 22%);
}
.auth-art-chip {
    position: absolute;
    top: 202px;
    left: 19px;
    display: flex;
    align-items: center;
    gap: 38px;
    padding: 13px 18px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    transform: rotate(-6deg);
}
.auth-art-chip span {
    font-size: 18px;
    opacity: 0.55;
}

.auth-panel {
    position: relative;
    width: 100%;
    padding: 36px;
    border-radius: 32px;
}
.auth-panel-heading {
    margin-bottom: 27px;
}
.auth-entry-mark {
    display: grid;
    width: 46px;
    height: 46px;
    place-items: center;
    margin-bottom: 23px;
    border-radius: 16px;
    color: var(--color-primary);
}
.auth-panel-heading h1 {
    font-size: 28px;
    font-weight: 650;
    letter-spacing: -0.045em;
    line-height: 1.2;
}
.auth-panel-heading p {
    margin-top: 10px;
    font-size: 13px;
    line-height: 1.65;
}
.auth-footer {
    gap: 16px;
    color: color-mix(in oklab, var(--color-base-content) 40%, transparent);
    font-size: 10px;
    letter-spacing: 0.07em;
}

.auth-panel :deep(.auth-form) {
    gap: 22px;
}
.auth-panel :deep(.auth-fields) {
    gap: 20px;
}
.auth-panel :deep(.auth-fields-register) {
    gap: 16px;
}
.auth-panel :deep(.label) {
    padding-block: 0;
    font-size: 12px;
    font-weight: 500;
}
.auth-panel :deep(.input) {
    min-height: 52px;
    border-radius: 15px;
    font-size: 15px;
}
.auth-panel :deep(.input::placeholder) {
    color: var(--glass-muted);
    opacity: 0.8;
}
.auth-panel :deep(.auth-submit),
.auth-panel :deep(.auth-secondary) {
    min-height: 52px;
    border-radius: 15px;
    font-size: 13px;
}
.auth-panel :deep(.auth-submit) {
    margin-top: 2px;
}
.auth-panel :deep(.auth-secondary) {
    border-color: color-mix(
        in oklab,
        var(--color-base-content) 14%,
        transparent
    );
    background: color-mix(in oklab, var(--color-base-content) 3%, transparent);
}
.auth-panel :deep(.auth-divider) {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 11px;
}
.auth-panel :deep(.auth-divider::before),
.auth-panel :deep(.auth-divider::after) {
    content: '';
    height: 1px;
    flex: 1;
    background: color-mix(in oklab, var(--color-base-content) 10%, transparent);
}
.auth-panel :deep(.auth-remember) {
    min-height: 28px;
}
.auth-panel :deep(.auth-remember .checkbox) {
    width: 18px;
    height: 18px;
    border-radius: 6px;
}
.auth-panel :deep(.auth-inline-link) {
    color: color-mix(
        in oklab,
        var(--color-primary) 75%,
        var(--color-base-content)
    );
    font-size: 12px;
}

@media (max-width: 1023px) {
    .auth-scene {
        padding: 22px 24px 18px;
    }
    .auth-main {
        max-width: 440px;
        grid-template-columns: minmax(0, 1fr);
        margin-block: 44px;
    }
    .auth-intro {
        display: none;
    }
    .auth-footer {
        max-width: 440px;
    }
    .auth-aurora {
        background:
            radial-gradient(
                ellipse at 10% 45%,
                rgb(72 159 212 / 15%),
                transparent 55%
            ),
            radial-gradient(
                ellipse at 100% 20%,
                rgb(132 124 230 / 13%),
                transparent 55%
            );
    }
}

@media (max-width: 480px) {
    .auth-scene {
        padding: 18px 18px 20px;
    }
    .auth-header {
        gap: 8px;
    }
    .auth-brand {
        gap: 8px;
    }
    .auth-brand-symbol {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        font-size: 22px;
    }
    .auth-brand-name {
        font-size: 14px;
        gap: 3px;
    }
    .auth-preferences {
        gap: 5px;
    }
    .auth-preferences :deep(.btn) {
        min-height: 38px;
        height: 38px;
        padding-inline: 10px;
        font-size: 11px;
    }
    .auth-preferences :deep(.btn-square) {
        width: 38px;
        padding-inline: 0;
    }
    .auth-main {
        margin-block: 34px;
    }
    .auth-panel {
        padding: 27px 24px;
        border-radius: 27px;
    }
    .auth-panel-heading h1 {
        font-size: 26px;
    }
    .auth-entry-mark {
        width: 42px;
        height: 42px;
        margin-bottom: 20px;
        border-radius: 14px;
    }
    .auth-panel :deep(.input) {
        font-size: 16px;
    }
    .auth-footer {
        font-size: 9px;
    }
}

@media (max-width: 359px) {
    .auth-brand-name {
        display: none;
    }
    .auth-panel {
        padding-inline: 19px;
    }
}
</style>
