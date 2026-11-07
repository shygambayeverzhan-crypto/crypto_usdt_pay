<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CallbackUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (function_exists('is_local') && is_local()) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            $fail(__('validation.url'));
            return;
        }

        $url = trim($value);
        $components = parse_url($url);

        if ($components === false || !isset($components['scheme'], $components['host']) || strtolower($components['scheme']) !== 'https') {
            $fail(__('validation.app.invoice.callback_https_only'));
            return;
        }

        $host = $this->normalizeHost($components['host']);

        if ($this->isLocalHost($host)) {
            $fail(__('validation.app.invoice.callback_private_ip'));
            return;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if ($this->isDisallowedIp($host)) {
                $fail(__('validation.app.invoice.callback_private_ip'));
            }

            return;
        }

        $resolvedIps = $this->resolveHostIps($host);
        if ($resolvedIps === []) {
            $fail(__('validation.app.invoice.callback_dns_failed'));
            return;
        }

        foreach ($resolvedIps as $ip) {
            if ($this->isDisallowedIp($ip)) {
                $fail(__('validation.app.invoice.callback_private_ip'));
                return;
            }
        }
    }

    private function normalizeHost(string $host): string
    {
        $trimmed = strtolower(trim($host));

        if (function_exists('idn_to_ascii')) {
            $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
            $ascii = idn_to_ascii($trimmed, IDNA_DEFAULT, $variant);
            if ($ascii !== false) {
                return $ascii;
            }
        }

        return $trimmed;
    }

    private function resolveHostIps(string $host): array
    {
        $ips = [];

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records !== false) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if ($ips === []) {
            $ipv4 = gethostbynamel($host);
            if (is_array($ipv4)) {
                $ips = [...$ipv4];
            }
        }

        return array_values(array_unique(array_filter($ips, static fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP) !== false)));
    }

    private function isLocalHost(string $host): bool
    {
        return $host === 'localhost';
    }

    private function isDisallowedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        $normalizedIp = strtolower($ip);

        if ($normalizedIp === '::1' || str_starts_with($normalizedIp, '127.')) {
            return true;
        }

        if ($this->isPrivateIpv4($normalizedIp) || $this->isLinkLocalIpv4($normalizedIp)) {
            return true;
        }

        if ($this->isUniqueLocalIpv6($normalizedIp) || $this->isLinkLocalIpv6($normalizedIp)) {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function isPrivateIpv4(string $ip): bool
    {
        return str_starts_with($ip, '10.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) === 1
            || str_starts_with($ip, '192.168.');
    }

    private function isLinkLocalIpv4(string $ip): bool
    {
        return str_starts_with($ip, '169.254.');
    }

    private function isUniqueLocalIpv6(string $ip): bool
    {
        return str_starts_with($ip, 'fc') || str_starts_with($ip, 'fd');
    }

    private function isLinkLocalIpv6(string $ip): bool
    {
        return str_starts_with($ip, 'fe80:');
    }
}

