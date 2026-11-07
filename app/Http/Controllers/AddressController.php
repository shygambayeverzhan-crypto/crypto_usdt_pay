<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Address\AddressFilterRequest;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressStatusRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\Address\AddressService;
use App\Enums\Currency;
use App\Enums\Network;
use App\Enums\NetworkCurrency;
use App\Exceptions\Address\AddressNotFoundOnBlockchainException;
use App\Exceptions\Address\AddressServiceException;
use App\Exceptions\Address\CurrencyNetworkMismatchException;
use App\Exceptions\Address\DuplicateAddressException;
use App\Exceptions\Address\UnsupportedCurrencyException;
use App\Exceptions\Address\UnsupportedNetworkException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class AddressController extends Controller
{
    /**
     * Display a listing of addresses.
     */
    public function index(AddressFilterRequest $request): Response
    {
        $userId = Auth::id();
        $filters = $request->filters();

        $paginator = Address::query()
            ->where('user_id', $userId)
            ->when($filters['currency'], fn (Builder $query, string $currency) => $query->where('currency', Currency::from($currency)))
            ->when($filters['network'], fn (Builder $query, string $network) => $query->where('network', Network::from($network)))
            ->when($filters['is_active'], fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['search'], function (Builder $query, string $search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('id', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($address) => (new AddressResource($address))->resolve());

        $currencyOptions = array_map(static function (Currency $c) {
            $value = $c->value;
            return [
                'value' => $value,
                'label' => strtoupper($value),
            ];
        }, Currency::cases());

        $networkOptions = array_map(static function (Network $n) {
            $value = $n->value;
            return [
                'value' => $value,
                'label' => strtoupper($value),
            ];
        }, Network::cases());

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

        return $this->inertia('addresses/Index', [
            'addresses' => $paginator,
            'currencyOptions' => $currencyOptions,
            'networkOptions' => $networkOptions,
            'currencyNetworkOptions' => $currencyNetworkOptions,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created address.
     */
    public function store(StoreAddressRequest $request, AddressService $addressService)
    {
        try {
            $address = $addressService->create(
                $request->user(),
                $request->string('currency')->toString(),
                $request->string('network')->toString(),
                $request->string('address')->toString(),
            );

            return $this->storeSuccessResponse($request, $address);
        } catch (AddressServiceException $exception) {
            $message = $this->mapServiceException($exception);

            return $this->storeErrorResponse($request, $message, HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);

            return $this->storeErrorResponse(
                $request,
                __('messages.addresses.add_failed'),
                HttpResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function storeSuccessResponse(StoreAddressRequest $request, Address $address): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('messages.addresses.added'),
                'address' => (new AddressResource($address))->resolve(),
            ], HttpResponse::HTTP_CREATED);
        }

        return back()->with('success', __('messages.addresses.added'));
    }

    private function storeErrorResponse(StoreAddressRequest $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        return back()
            ->with('error', $message)
            ->onlyInput('currency', 'network', 'address');
    }

    private function mapServiceException(AddressServiceException $exception): string
    {
        return match (true) {
            $exception instanceof AddressNotFoundOnBlockchainException => __('messages.addresses.errors.not_found_blockchain'),
            $exception instanceof DuplicateAddressException => __('messages.addresses.errors.duplicate'),
            $exception instanceof UnsupportedCurrencyException => __('messages.addresses.errors.unsupported_currency'),
            $exception instanceof UnsupportedNetworkException => __('messages.addresses.errors.unsupported_network'),
            $exception instanceof CurrencyNetworkMismatchException => __('messages.addresses.errors.currency_mismatch'),
            default => $exception->getMessage() ?: __('messages.addresses.add_failed'),
        };
    }

    /**
     * Update the specified address (enable/disable).
     */
    public function update(UpdateAddressStatusRequest $request, Address $address, AddressService $addressService)
    {
        $this->authorizeAddress($address);

        $isActive = (bool) $request->boolean('is_active');

        if ($isActive) {
            $addressService->enable($address);
        } else {
            $addressService->disable($address);
        }

        return back()->with('success', __('messages.addresses.updated'));
    }

    private function authorizeAddress(Address $address): void
    {
        if ($address->user_id !== Auth::id()) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }
    }
}


