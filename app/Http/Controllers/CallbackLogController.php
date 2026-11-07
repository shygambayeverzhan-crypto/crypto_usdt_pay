<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CallbackLogFilterRequest;
use App\Http\Resources\InvoiceCallbackLogResource;
use App\Models\InvoiceCallbackLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class CallbackLogController extends Controller
{
    public function index(CallbackLogFilterRequest $request): Response
    {
        $filters = $request->filters();

        $paginator = InvoiceCallbackLog::query()
            ->whereHas('invoice', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->when($filters['status_group'] === 'success', fn (Builder $q) => $q->whereBetween('response_status', [200, 299]))
            ->when($filters['status_group'] === 'error', fn (Builder $q) => $q->where('response_status', '>=', 400))
            ->when($filters['has_error'], fn (Builder $q) => $q->whereNotNull('error_message'))
            ->when($filters['event'], fn (Builder $q, string $event) => $q->where('event', 'like', '%' . $event . '%'))
            ->when($filters['invoice_id'], fn (Builder $q, string $invoiceId) => $q->where('invoice_id', 'like', '%' . $invoiceId . '%'))
            ->when($filters['search'], function (Builder $q, string $search) {
                $term = '%' . $search . '%';

                $q->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('id', 'like', $term)
                        ->orWhere('invoice_id', 'like', $term)
                        ->orWhere('event', 'like', $term)
                        ->orWhere('url', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($log) => (new InvoiceCallbackLogResource($log))->resolve());

        return $this->inertia('callbacks/Index', [
            'logs' => $paginator,
            'filters' => $filters,
        ]);
    }
}


