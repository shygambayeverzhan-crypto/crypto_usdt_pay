<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\InvoiceStatus;
use App\Enums\Currency;
use App\Enums\Network;
use App\Enums\NetworkCurrency;
use App\Contracts\Client\ClientServiceContract;
use App\Exceptions\Client\ClientException;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\InvoiceFilterRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\ClientResource;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use App\Contracts\Invoice\InvoiceCallbackServiceContract;
use Throwable;

class InvoiceController extends Controller
{
    public function index(InvoiceFilterRequest $request): Response
    {
        $filters = $request->filters();

        $paginator = Invoice::query()
            ->where('user_id', Auth::id())
            ->with(['address', 'merchant', 'client'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', InvoiceStatus::from($status)))
            ->when($filters['currency'], fn (Builder $query, string $currency) => $query->where('currency', Currency::from($currency)))
            ->when($filters['network'], fn (Builder $query, string $network) => $query->where('network', Network::from($network)))
            ->when($filters['merchant_id'], fn (Builder $query, string $merchantId) => $query->where('merchant_id', $merchantId))
            ->when($filters['client_id'], fn (Builder $query, string $clientId) => $query->where('client_id', $clientId))
            ->when($filters['has_callback'], fn (Builder $query) => $query->whereNotNull('callback_url'))
            ->when($filters['search'], function (Builder $query, string $search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('id', 'like', $term)
                        ->orWhere('external_invoice_id', 'like', $term)
                        ->orWhereHas('client', fn (Builder $client) => $client->where('external_id', 'like', $term)->orWhere('name', 'like', $term))
                        ->orWhere('tag', 'like', $term)
                        ->orWhereHas('address', fn (Builder $address) => $address->where('address', 'like', $term));
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($invoice) => (new InvoiceResource($invoice))->resolve());

        $currencyOptions = array_map(fn (Currency $c) => ['value' => $c->value, 'label' => $c->value], Currency::cases());
        $networkOptions = array_map(fn (Network $n) => ['value' => $n->value, 'label' => strtoupper($n->value)], Network::cases());

        // Список поддерживаемых пар валюта+сеть для UI создания инвойса
        $currencyNetworkOptions = [];
        foreach (Currency::cases() as $currency) {
            foreach (NetworkCurrency::networksByCurrency($currency) as $network) {
                $currencyNetworkOptions[] = [
                    'value' => $currency->value . '|' . $network->value,
                    'currency' => $currency->value,
                    'currency_label' => strtoupper($currency->value),
                    'network' => $network->value,
                    'network_label' => strtoupper($network->value),
                ];
            }
        }
        $merchantOptions = Merchant::query()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->get()
            ->map(fn (Merchant $merchant) => (new MerchantResource($merchant))->resolve())
            ->map(fn (array $merchant) => [
                'value' => (string) ($merchant['id'] ?? ''),
                'label' => (string) ($merchant['name'] ?? ''),
                'initials' => $merchant['initials'] ?? '',
                'logo_url' => $merchant['logo_url'] ?? null,
                'description' => $merchant['description'] ?? null,
            ])
            ->values();
        $clientOptions = Client::query()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->get()
            ->map(fn (Client $client) => (new ClientResource($client))->resolve())
            ->map(fn (array $client) => [
                'id' => (string) ($client['id'] ?? ''),
                'value' => (string) ($client['external_id'] ?? ''),
                'label' => (string) ($client['name'] ?? $client['external_id'] ?? ''),
                'contact' => $client['contact'] ?? $client['telegram'] ?? null,
                'external_id' => $client['external_id'] ?? '',
            ])
            ->values();

        return $this->inertia('invoices/Index', [
            'invoices' => $paginator,
            'currencyOptions' => $currencyOptions,
            'networkOptions' => $networkOptions,
            'currencyNetworkOptions' => $currencyNetworkOptions,
            'merchantOptions' => $merchantOptions,
            'clientOptions' => $clientOptions,
            'statuses' => [
                'active' => InvoiceStatus::active(),
                'final' => InvoiceStatus::final(),
            ],
            'filters' => $filters,
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceServiceContract $service, MoneyServiceContract $money, ClientServiceContract $clients): JsonResponse|RedirectResponse
    {
        try {
            $client = null;
            $clientExternalId = $request->clientExternalId();
            if ($clientExternalId !== null) {
                $client = $clients->findOrCreate(
                    $request->user(),
                    $clientExternalId,
                    $request->clientName(),
                    $request->clientTelegram(),
                    $request->clientContact()
                );
            }

            $invoice = $service->create(
                $request->user(),
                $request->toCurrencyEnum(),
                $request->toNetworkEnum(),
                $request->toMoneyAmount($money),
                $request->input('external_invoice_id'),
                $request->input('callback_url'),
                $request->input('tag'),
                (array) $request->input('metadata', []),
                $request->merchant(),
                $client,
                $request->productName(),
                $request->productDescription()
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'invoice' => (new InvoiceResource($invoice->load(['address', 'merchant', 'client'])))->resolve(),
                ]);
            }

            return back()->with('success', __('messages.invoices.created'));
        } catch (ClientException $exception) {
            $message = $exception->getMessage() ?: __('messages.clients.create_failed');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        } catch (Throwable $e) {
            report($e);
            $message = $e->getMessage() ?: __('messages.invoices.create_failed');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }
    }

    public function show(Invoice $invoice): array
    {
        $this->authorizeInvoice($invoice);

        return (new InvoiceResource($invoice->load(['address', 'merchant', 'client'])))->resolve();
    }

    public function public(Invoice $invoice): Response
    {
        $invoice->load(['address', 'client']);

        return $this->inertia('PaymentForm/Index', [
            'invoice' => (new InvoiceResource($invoice->loadMissing(['merchant'])))->resolve(),
            'appName' => config('app.name'),
            'statuses' => [
                'active' => InvoiceStatus::active(),
                'final' => InvoiceStatus::final(),
            ],
        ]);
    }

    public function publicData(Invoice $invoice): array
    {
        $invoice->load(['address', 'merchant', 'client']);

        return (new InvoiceResource($invoice))->resolve();
    }

    public function publicQr(Invoice $invoice)
    {
        $invoice->load('address');

        $address = $invoice->address?->address;
        if (!$address) {
            abort(404);
        }

        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $address,
            size: 400,
            margin: 10,
        );

        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceServiceContract $service): JsonResponse|RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        try {
            $status = InvoiceStatus::from($request->input('status'));
            $txid = $request->input('txid');

            $updated = $service->updateStatusManually($invoice, $status, $txid);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'invoice' => (new InvoiceResource($updated->loadMissing(['client'])))->resolve(),
                ]);
            }

            return back()->with('success', __('messages.invoices.updated'));
        } catch (Throwable $e) {
            report($e);
            $message = $e->getMessage() ?: __('messages.invoices.update_failed');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }
    }

    public function sendCallback(Invoice $invoice, InvoiceCallbackServiceContract $callbackService)
    {
        $this->authorizeInvoice($invoice);

        // Отправляем колбэк только если указан callback_url (сервис внутри сам проверит)
        $callbackService->send($invoice, 'manual');

        return response()->json([
            'success' => true,
        ]);
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(404);
        }
    }
}


