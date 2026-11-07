<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Models\Address;
use App\Models\Invoice;
use App\Models\InvoiceCallbackLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MoneyServiceContract $moneyService,
    ) {}

    /**
     * Display the dashboard page.
     */
    public function index(): Response
    {
        $userId = Auth::id();
        $firstInvoiceDate = Invoice::query()
            ->where('user_id', $userId)
            ->min('created_at');
        $firstPaidDate = Invoice::query()
            ->where('user_id', $userId)
            ->where('status', InvoiceStatus::PAID)
            ->min('updated_at');
        $periodStart = collect([$firstInvoiceDate, $firstPaidDate])
            ->filter()
            ->map(fn (string $date): CarbonImmutable => CarbonImmutable::parse($date)->startOfDay())
            ->min() ?? CarbonImmutable::now()->startOfDay();
        $periodEnd = CarbonImmutable::now()->startOfDay();
        $periodDays = (int) $periodStart->diffInDays($periodEnd);

        $totalInvoices = Invoice::query()->where('user_id', $userId)->count();
        $paidInvoices = Invoice::query()->where('user_id', $userId)->where('status', InvoiceStatus::PAID)->count();
        $activeInvoices = Invoice::query()->where('user_id', $userId)->whereIn('status', InvoiceStatus::active())->count();
        $expiredInvoices = Invoice::query()->where('user_id', $userId)->where('status', InvoiceStatus::EXPIRED)->count();
        $cancelledInvoices = Invoice::query()->where('user_id', $userId)->where('status', InvoiceStatus::CANCELLED)->count();
        $addressesTotal = Address::query()->where('user_id', $userId)->count();

        $successRate = $totalInvoices > 0
            ? round(($paidInvoices / $totalInvoices) * 100, 2)
            : 0.0;

        $invoicesByDay = Invoice::query()
            ->where('user_id', $userId)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $paidByDay = Invoice::query()
            ->where('user_id', $userId)
            ->where('status', InvoiceStatus::PAID)
            ->selectRaw('DATE(updated_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $timeline = collect(range(0, $periodDays))
            ->map(function (int $dayOffset) use ($invoicesByDay, $paidByDay, $periodStart): array {
                $date = $periodStart->addDays($dayOffset);
                $dateKey = $date->toDateString();

                return [
                    'date' => $date->format('d.m'),
                    'created' => (int) ($invoicesByDay[$dateKey] ?? 0),
                    'paid' => (int) ($paidByDay[$dateKey] ?? 0),
                ];
            })
            ->values();

        $turnoverByCurrency = Invoice::query()
            ->where('user_id', $userId)
            ->where('status', InvoiceStatus::PAID)
            ->selectRaw('currency, SUM(CAST(amount AS DECIMAL(36, 0))) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(function ($row) use ($periodStart, $periodDays, $userId): array {
                $currencyValue = $row->currency instanceof Currency
                    ? $row->currency->value
                    : (string) $row->currency;
                $currency = Currency::from($currencyValue);
                $turnoverByDay = Invoice::query()
                    ->where('user_id', $userId)
                    ->where('status', InvoiceStatus::PAID)
                    ->where('currency', $currency)
                    ->selectRaw('DATE(updated_at) as day, SUM(CAST(amount AS DECIMAL(36, 0))) as total')
                    ->groupBy('day')
                    ->pluck('total', 'day');

                return [
                    'currency' => strtoupper($currency->value),
                    'total' => $this->formatMinorAmount((string) $row->total, $currency),
                    'series' => collect(range(0, $periodDays))
                        ->map(function (int $dayOffset) use ($turnoverByDay, $currency, $periodStart): array {
                            $date = $periodStart->addDays($dayOffset);
                            $dateKey = $date->toDateString();

                            return [
                                'date' => $date->format('d.m'),
                                'amount' => $this->formatMinorAmount((string) ($turnoverByDay[$dateKey] ?? '0'), $currency),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        $statusBreakdown = collect(InvoiceStatus::cases())
            ->map(fn (InvoiceStatus $status): array => [
                'name' => __("frontend.dashboard.statuses.{$status->value}"),
                'value' => Invoice::query()->where('user_id', $userId)->where('status', $status)->count(),
            ])
            ->values();

        $networkBreakdown = Invoice::query()
            ->where('user_id', $userId)
            ->selectRaw('network, COUNT(*) as total')
            ->groupBy('network')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'name' => strtoupper((string) $invoice->network->value),
                'value' => (int) $invoice->total,
            ])
            ->values();

        $callbackLogs = InvoiceCallbackLog::query()
            ->whereHas('invoice', fn ($query) => $query->where('user_id', $userId));

        $callbackTotal = (clone $callbackLogs)->count();
        $callbackSuccessful = (clone $callbackLogs)
            ->whereBetween('response_status', [200, 299])
            ->count();

        $callbackSuccessRate = $callbackTotal > 0
            ? round(($callbackSuccessful / $callbackTotal) * 100, 2)
            : 0.0;

        $topClients = Invoice::query()
            ->where('user_id', $userId)
            ->whereNotNull('client_id')
            ->with('client:id,name,external_id')
            ->selectRaw('client_id, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_total', [InvoiceStatus::PAID->value])
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'name' => $invoice->client?->name ?: $invoice->client?->external_id ?: __('frontend.dashboard.charts.unknown_client'),
                'externalId' => $invoice->client?->external_id,
                'invoices' => (int) $invoice->total,
                'paidInvoices' => (int) $invoice->paid_total,
            ])
            ->values();

        return $this->inertia('Dashboard', [
            'stats' => [
                'totalInvoices' => $totalInvoices,
                'paidInvoices' => $paidInvoices,
                'activeInvoices' => $activeInvoices,
                'expiredInvoices' => $expiredInvoices,
                'cancelledInvoices' => $cancelledInvoices,
                'addressesTotal' => $addressesTotal,
                'successRate' => $successRate,
                'callbackSuccessRate' => $callbackSuccessRate,
            ],
            'charts' => [
                'timeline' => $timeline,
                'turnoverByCurrency' => $turnoverByCurrency,
                'statusBreakdown' => $statusBreakdown,
                'networkBreakdown' => $networkBreakdown,
                'topClients' => $topClients,
            ],
        ]);
    }

    private function formatMinorAmount(string $minorAmount, Currency $currency): float
    {
        return (float) $this->moneyService->format(
            $this->moneyService->fromMinor($minorAmount, $currency),
            false,
        );
    }
}
