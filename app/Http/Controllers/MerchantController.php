<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Merchant\MerchantServiceContract;
use App\Exceptions\Merchant\MerchantLogoInvalidException;
use App\Exceptions\Merchant\MerchantLogoNotSquareException;
use App\Exceptions\Merchant\MerchantLogoTooLargeException;
use App\Exceptions\Merchant\MerchantServiceException;
use App\Http\Requests\Merchant\MerchantFilterRequest;
use App\Http\Requests\Merchant\MerchantStoreRequest;
use App\Http\Requests\Merchant\MerchantUpdateRequest;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MerchantController extends Controller
{
    private const DEFAULT_INVOICE_EXPIRES_IN_MINUTES = 30;

    public function index(MerchantFilterRequest $request): InertiaResponse
    {
        $userId = Auth::id();
        $filters = $request->filters();

        $merchants = Merchant::query()
            ->where('user_id', $userId)
            ->when($filters['white_label_enabled'], fn (Builder $query) => $query->where('white_label_enabled', true))
            ->when($filters['search'], function (Builder $query, string $search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('initials', 'like', $term)
                        ->orWhere('id', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($merchant) => (new MerchantResource($merchant))->resolve());

        return $this->inertia('merchants/Index', [
            'merchants' => $merchants,
            'filters' => $filters,
        ]);
    }

    public function store(MerchantStoreRequest $request, MerchantServiceContract $service): JsonResponse|RedirectResponse
    {
        try {
            $invoiceExpiresInMinutes = (int) $request->input('invoice_expires_in_minutes', self::DEFAULT_INVOICE_EXPIRES_IN_MINUTES);
            $backUrl = $request->filled('back_url') ? $request->string('back_url')->trim()->toString() : null;

            $merchant = $service->create(
                $request->user(),
                $request->string('name')->toString(),
                $request->filled('description') ? $request->string('description')->toString() : null,
                $request->string('initials')->toString(),
                $backUrl,
                $request->boolean('white_label_enabled', true),
                $invoiceExpiresInMinutes,
                $request->file('logo')
            );

            return $this->successResponse($request, $merchant, Response::HTTP_CREATED);
        } catch (MerchantServiceException $exception) {
            return $this->errorResponse($request, $this->mapException($exception), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);
            return $this->errorResponse($request, __('messages.merchants.create_failed'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(MerchantUpdateRequest $request, Merchant $merchant, MerchantServiceContract $service): RedirectResponse|JsonResponse
    {
        $this->authorizeMerchant($merchant);

        try {
            $invoiceExpiresInMinutes = (int) $request->input('invoice_expires_in_minutes', (int) ($merchant->invoice_expires_in_minutes ?? self::DEFAULT_INVOICE_EXPIRES_IN_MINUTES));
            $backUrl = $request->has('back_url')
                ? ($request->filled('back_url') ? $request->string('back_url')->trim()->toString() : null)
                : $merchant->back_url;

            $service->update(
                $merchant,
                $request->string('name')->toString(),
                $request->filled('description') ? $request->string('description')->toString() : null,
                $request->string('initials')->toString(),
                $backUrl,
                $request->boolean('white_label_enabled', true),
                $invoiceExpiresInMinutes,
                $request->file('logo')
            );

            return $this->successResponse($request, $merchant->refresh());
        } catch (MerchantServiceException $exception) {
            return $this->errorResponse($request, $this->mapException($exception), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);
            return $this->errorResponse($request, __('messages.merchants.update_failed'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function authorizeMerchant(Merchant $merchant): void
    {
        if ($merchant->user_id !== Auth::id()) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    private function successResponse(MerchantStoreRequest|MerchantUpdateRequest $request, Merchant $merchant, int $status = Response::HTTP_OK): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'merchant' => (new MerchantResource($merchant))->resolve(),
            ], $status);
        }

        $message = $status === Response::HTTP_CREATED
            ? __('messages.merchants.created')
            : __('messages.merchants.updated');

        return back()->with('success', $message);
    }

    private function errorResponse(MerchantStoreRequest|MerchantUpdateRequest $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }

    private function mapException(MerchantServiceException $exception): string
    {
        return match (true) {
            $exception instanceof MerchantLogoInvalidException => __('messages.merchants.errors.logo_invalid'),
            $exception instanceof MerchantLogoNotSquareException => __('messages.merchants.errors.logo_not_square'),
            $exception instanceof MerchantLogoTooLargeException => __('messages.merchants.errors.logo_too_large'),
            default => $exception->getMessage() ?: __('messages.merchants.create_failed'),
        };
    }
}

