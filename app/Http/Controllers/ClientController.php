<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Client\ClientServiceContract;
use App\Exceptions\Client\ClientException;
use App\Http\Requests\Client\ClientFilterRequest;
use App\Http\Requests\Client\ClientStoreRequest;
use App\Http\Requests\Client\ClientUpdateRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ClientController extends Controller
{
    public function index(ClientFilterRequest $request): InertiaResponse
    {
        $filters = $request->filters();

        $clients = Client::query()
            ->where('user_id', Auth::id())
            ->when($filters['search'], function (Builder $query, string $search) {
                $term = '%' . $search . '%';

                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('external_id', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('telegram', 'like', $term)
                        ->orWhere('contact', 'like', $term);
                });
            })
            ->when($filters['has_contact'], function (Builder $query) {
                $query->where(function (Builder $nested) {
                    $nested->whereNotNull('telegram')->orWhereNotNull('contact');
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Client $client) => (new ClientResource($client))->resolve());

        return $this->inertia('clients/Index', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    public function store(ClientStoreRequest $request, ClientServiceContract $service): JsonResponse|RedirectResponse
    {
        try {
            $client = $service->create(
                $request->user(),
                $request->externalId(),
                $request->clientName(),
                $request->clientTelegram(),
                $request->clientContact()
            );

            return $this->successResponse($request, $client, Response::HTTP_CREATED);
        } catch (ClientException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);
            return $this->errorResponse($request, __('messages.clients.create_failed'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ClientUpdateRequest $request, Client $client, ClientServiceContract $service): JsonResponse|RedirectResponse
    {
        $this->authorizeClient($client);

        try {
            $client->external_id = $request->externalId();
            $client->save();

            $service->update(
                $client,
                $request->clientName(),
                $request->clientTelegram(),
                $request->clientContact()
            );

            return $this->successResponse($request, $client);
        } catch (ClientException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            report($exception);
            return $this->errorResponse($request, __('messages.clients.update_failed'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function authorizeClient(Client $client): void
    {
        if ($client->user_id !== Auth::id()) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    private function successResponse(ClientStoreRequest|ClientUpdateRequest $request, Client $client, int $status = Response::HTTP_OK): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'client' => (new ClientResource($client))->resolve(),
            ], $status);
        }

        $message = $status === Response::HTTP_CREATED
            ? __('messages.clients.created')
            : __('messages.clients.updated');

        return back()->with('success', $message);
    }

    private function errorResponse(ClientStoreRequest|ClientUpdateRequest $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }
}


