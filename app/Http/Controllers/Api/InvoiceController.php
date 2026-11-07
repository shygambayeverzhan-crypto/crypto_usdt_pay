<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Contracts\Client\ClientServiceContract;
use App\Exceptions\Client\ClientException;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoiceIndexRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(InvoiceIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $paginator = Invoice::query()
            ->where('user_id', $request->user()?->id)
            ->with(['merchant', 'client'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', InvoiceStatus::from($status)))
            ->when($filters['currency'], fn (Builder $query, string $currency) => $query->where('currency', Currency::from($currency)))
            ->when($filters['network'], fn (Builder $query, string $network) => $query->where('network', Network::from($network)))
            ->when($filters['merchant_id'], fn (Builder $query, int $merchantId) => $query->where('merchant_id', $merchantId))
            ->when($filters['client_external_id'], fn (Builder $query, string $clientExternalId) => $query->whereHas('client', fn (Builder $client) => $client->where('external_id', $clientExternalId)))
            ->when($filters['external_invoice_id'], fn (Builder $query, string $externalId) => $query->where('external_invoice_id', 'like', '%' . $externalId . '%'))
            ->when($filters['tag'], fn (Builder $query, string $tag) => $query->where('tag', 'like', '%' . $tag . '%'))
            ->when($filters['has_callback'], fn (Builder $query) => $query->whereNotNull('callback_url'))
            ->when($filters['search'], function (Builder $query, string $search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('id', 'like', $term)
                        ->orWhere('external_invoice_id', 'like', $term)
                        ->orWhere('tag', 'like', $term)
                        ->orWhereHas('client', fn (Builder $client) => $client->where('external_id', 'like', $term)->orWhere('name', 'like', $term))
                        ->orWhereHas('address', fn (Builder $address) => $address->where('address', 'like', $term));
                });
            })
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return InvoiceResource::collection($paginator);
    }

    public function store(StoreInvoiceRequest $request, InvoiceServiceContract $service, MoneyServiceContract $money, ClientServiceContract $clients)
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

            return response()->json((new InvoiceResource($invoice->load(['address', 'merchant', 'client'])))->resolve());
        } catch (ClientException $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: __('messages.clients.create_failed'),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Invoice $invoice): array
    {
        $this->authorizeInvoice($invoice);

        return (new InvoiceResource($invoice->load(['address', 'merchant', 'client'])))->resolve();
    }

    public function status(Invoice $invoice): array
    {
        $this->authorizeInvoice($invoice);

        $data = (new InvoiceResource($invoice->loadMissing('client')))->resolve();

        return [
            'id' => $data['id'] ?? $invoice->id,
            'status' => $data['status'] ?? $invoice->status->value,
            'amount' => $data['amount'] ?? null,
            'amount_received' => $data['amount_received'] ?? null,
            'confirmations' => $data['confirmations'] ?? 0,
            'expires_at' => $data['expires_at'] ?? null,
            'txid' => $data['txid'] ?? null,
        ];
    }

    public function public(Invoice $invoice): array
    {
        $this->authorizeInvoice($invoice);

        $invoice->load(['address', 'merchant', 'client']);

        return (new InvoiceResource($invoice))->resolve();
    }

    public function qr(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);

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

    public function cancel(Invoice $invoice, InvoiceServiceContract $service)
    {
        $this->authorizeInvoice($invoice);

        if ($invoice->status->isFinal()) {
            return response()->json([
                'message' => __('messages.invoices.already_finalized'),
            ], 409);
        }

        if ($invoice->expires_at && now()->greaterThanOrEqualTo($invoice->expires_at)) {
            return response()->json([
                'message' => __('messages.invoices.already_expired'),
            ], 409);
        }

        $service->expire($invoice);

        return response()->json((new InvoiceResource($invoice->refresh()))->resolve());
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(404);
        }
    }
}


