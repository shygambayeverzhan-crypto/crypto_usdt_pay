<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreApiAllowedIpRequest;
use App\Http\Resources\ApiTokenAllowedIpResource;
use App\Models\ApiToken;
use App\Models\ApiTokenAllowedIp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApiTokenAllowedIpController extends Controller
{
    public function store(StoreApiAllowedIpRequest $request): JsonResponse
    {
        $token = ApiToken::query()
            ->where('id', (int) $request->input('api_token_id'))
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $ip = strtolower(trim((string) $request->input('ip')));

        $token->allowedIps()->create([
            'ip' => $ip,
        ]);

        $allowedIps = ApiTokenAllowedIpResource::collection(
            $token->allowedIps()->orderByDesc('created_at')->get()
        )->resolve();

        return response()->json([
            'allowed_ips' => $allowedIps,
        ]);
    }

    public function destroy(ApiTokenAllowedIp $allowedIp): JsonResponse
    {
        if ($allowedIp->apiToken->user_id !== Auth::id()) {
            abort(403);
        }

        $token = $allowedIp->apiToken;
        $allowedIp->delete();

        $allowedIps = ApiTokenAllowedIpResource::collection(
            $token->allowedIps()->orderByDesc('created_at')->get()
        )->resolve();

        return response()->json([
            'allowed_ips' => $allowedIps,
        ]);
    }
}


