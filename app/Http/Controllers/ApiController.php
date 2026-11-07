<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ApiTokenAllowedIpResource;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class ApiController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();
        $token = null;
        $callbackToken = null;

        if ($user instanceof User) {
            $token = $this->ensureToken(ApiToken::NAME_DEFAULT);
            $callbackToken = $this->ensureToken(ApiToken::NAME_CALLBACK);
        }

        $allowedIps = $token
            ? ApiTokenAllowedIpResource::collection(
                $token->allowedIps()->orderByDesc('created_at')->get()
            )->resolve()
            : [];

        return $this->inertia('api/Index', [
            'publicApiKey' => $token?->token ?? '',
            'callbackToken' => $callbackToken?->token ?? '',
            'apiBaseUrl' => url('/api/v1'),
            'apiTokenId' => $token?->id,
            'allowedIps' => $allowedIps,
        ]);
    }

    public function regenerate(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        $token = $user->apiTokens()
            ->where('name', ApiToken::NAME_DEFAULT)
            ->first();

        if (! $token) {
            abort(404, 'API token not found');
        }

        $token->update([
            'token' => ApiToken::generateUniqueToken(),
        ]);

        return redirect()
            ->route('api.docs')
            ->with('success', __('frontend.api.token.refreshed'));
    }

    public function regenerateCallback(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        $token = $user->apiTokens()
            ->where('name', ApiToken::NAME_CALLBACK)
            ->first();

        if (! $token) {
            abort(404, 'Callback token not found');
        }

        $token->update([
            'token' => ApiToken::generateUniqueToken(),
        ]);

        return redirect()
            ->route('api.docs')
            ->with('success', __('frontend.api.token.callback_refreshed'));
    }

    private function ensureToken(string $name): ApiToken
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        $token = $user->apiTokens()
            ->where('name', $name)
            ->first();

        if ($token) {
            return $token;
        }

        return $user->apiTokens()->create([
            'name' => $name,
            'token' => ApiToken::generateUniqueToken(),
        ]);
    }
}
