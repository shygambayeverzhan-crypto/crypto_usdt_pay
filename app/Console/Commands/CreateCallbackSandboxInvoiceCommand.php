<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use App\Jobs\SendSandboxInvoicePaymentJob;
use App\Models\Client;
use App\Models\Merchant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateCallbackSandboxInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'invoices:callback-sandbox';

    /**
     * The console command description.
     */
    protected $description = 'Создать инвойс для проверки callback по адресу /dev/callback-sandbox';

    public function handle(InvoiceServiceContract $invoices, MoneyServiceContract $money): int
    {
        $currency = Currency::USDT;
        $network = Network::TRON;

        $merchantQuery = Merchant::query()
            ->whereHas('user.clients')
            ->orderBy('id');

        $merchantCount = $merchantQuery->count();
        if ($merchantCount <= 0) {
            $this->warn('Нет мерчантов с клиентами. Инвойс не создан.');
            return self::SUCCESS;
        }

        $merchant = $merchantQuery
            ->offset(random_int(0, $merchantCount - 1))
            ->first();

        if ($merchant === null) {
            $this->warn('Не удалось выбрать merchant. Инвойс не создан.');
            return self::SUCCESS;
        }

        $clientQuery = Client::query()
            ->where('user_id', $merchant->user_id)
            ->orderBy('id');

        $clientCount = $clientQuery->count();
        if ($clientCount <= 0) {
            $this->warn('Не удалось выбрать client. Инвойс не создан.');
            return self::SUCCESS;
        }

        $client = $clientQuery
            ->offset(random_int(0, $clientCount - 1))
            ->first();

        if ($client === null) {
            $this->warn('Не удалось выбрать client. Инвойс не создан.');
            return self::SUCCESS;
        }

        $callbackUrl = rtrim((string) config('app.url'), '/') . '/dev/callback-sandbox';
        $amount = $this->randomAmount();
        $externalInvoiceId = $this->makeExternalInvoiceId();
        $product = $this->randomProduct();

        $invoice = $invoices->create(
            user: $merchant->user,
            currency: $currency,
            network: $network,
            amount: $money->parse($amount, $currency),
            externalInvoiceId: $externalInvoiceId,
            callbackUrl: $callbackUrl,
            tag: null,
            metadata: [],
            merchant: $merchant,
            client: $client,
            productName: $product['name'],
            productDescription: $product['description'],
        );

        $this->info('Инвойс создан: ' . $invoice->id);
        $this->line('external_invoice_id: ' . (string) $invoice->external_invoice_id);
        $this->line('amount: ' . $amount . ' ' . $currency->value);
        $this->line('merchant_id: ' . (string) $merchant->id);
        $this->line('client_id: ' . (string) $client->id);
        $this->line('callback_url: ' . $callbackUrl);
        $this->line('product: ' . $product['name'] . ' / ' . $product['description']);

        $this->maybeSchedulePaymentJob($invoice->id);

        return self::SUCCESS;
    }

    private function randomAmount(): string
    {
        $integer = random_int(10, 100);
        $withDecimals = (bool) random_int(0, 1);

        if (!$withDecimals) {
            return (string) $integer;
        }

        $decimals = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);

        return $integer . '.' . $decimals;
    }

    private function makeExternalInvoiceId(): string
    {
        return 'inv_' . Str::uuid()->toString();
    }

    private function maybeSchedulePaymentJob(string $invoiceId): void
    {
        if (app()->environment('production')) {
            return;
        }

        $chance = random_int(70, 90);
        $roll = random_int(1, 100);

        if ($roll > $chance) {
            $this->line("Оплата не запланирована (шанс {$chance}%, roll {$roll})");
            return;
        }

        $delaySeconds = random_int(60, 180);

        SendSandboxInvoicePaymentJob::dispatch($invoiceId)
            ->delay(now()->addSeconds($delaySeconds));

        $this->line("Оплата запланирована через {$delaySeconds} сек (шанс {$chance}%, roll {$roll})");
    }

    /**
     * @return array{name:string,description:string}
     */
    private function randomProduct(): array
    {
        $products = [
            ['name' => 'Подписка на месяц', 'description' => 'Доступ на 30 дней. Отмена в любой момент.'],
            ['name' => 'Мини-курс: старт', 'description' => 'Короткий урок с примерами. Доступ сразу после оплаты.'],
            ['name' => 'Шаблон для запуска', 'description' => 'Готовый файл для быстрого старта. Инструкция внутри.'],
            ['name' => 'Доступ по ссылке', 'description' => 'Мгновенная активация по ссылке. Без ожидания и ручной проверки.'],
            ['name' => 'Лицензия на продукт', 'description' => 'Персональный ключ. Можно перенести на другое устройство.'],
            ['name' => 'Набор материалов', 'description' => 'Базовый пакет материалов. Подойдёт для старта.'],
            ['name' => 'Апгрейд возможностей', 'description' => 'Расширение возможностей. Новые функции и лимиты.'],
            ['name' => 'Сессия с экспертом', 'description' => 'Консультация 20 минут. Разбор одного вопроса.'],
            ['name' => 'Пакет токенов', 'description' => 'Пополнение баланса для операций. Списания по факту использования.'],
            ['name' => 'Промо на оплату', 'description' => 'Скидка на следующий платёж. Действует ограниченное время.'],
        ];

        return $products[random_int(0, count($products) - 1)];
    }
}


