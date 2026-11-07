<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Client\ClientServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClientIndexRequest;
use App\Http\Requests\Client\ClientStoreRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    public function index(ClientIndexRequest $request): AnonymousResourceCollection
    {
        $userId = $request->user()?->id;

        if ($userId === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $paginator = Client::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return ClientResource::collection($paginator);
    }

    public function store(ClientStoreRequest $request, ClientServiceContract $service): array
    {
        $client = $service->findOrCreate(
            $request->user(),
            $request->externalId(),
            $request->clientName(),
            $request->clientTelegram(),
            $request->clientContact()
        );

        return [
            'client' => (new ClientResource($client))->resolve(),
        ];
    }
}


