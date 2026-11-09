<?php

declare(strict_types=1);

namespace App\Support;

final class PaymentRequisiteOptions
{
    public const BANKS = [
        'kaspi' => 'Kaspi Bank',
        'halyk' => 'Halyk Bank',
        'freedom' => 'Freedom Bank',
        'bcc' => 'Bank CenterCredit',
        'forte' => 'ForteBank',
        'other' => 'Other',
    ];

    public const NETWORKS = [
        'tron' => 'TRON (TRC-20)',
        'ethereum' => 'Ethereum (ERC-20)',
        'bsc' => 'BNB Smart Chain (BEP-20)',
    ];

    public const CARD_CURRENCIES = ['KZT', 'USD', 'EUR', 'RUB'];

    public static function banks(): array
    {
        $banks = self::BANKS;
        $banks['other'] = app()->getLocale() === 'ru' ? 'Другой банк' : 'Other bank';

        return self::options($banks);
    }

    public static function networks(): array
    {
        return self::options(self::NETWORKS);
    }

    public static function currencies(): array
    {
        return array_map(fn (string $currency): array => ['value' => $currency, 'label' => $currency], self::CARD_CURRENCIES);
    }

    private static function options(array $items): array
    {
        $options = [];
        foreach ($items as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
