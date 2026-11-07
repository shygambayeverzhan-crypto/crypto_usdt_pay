<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request): array
    {
        $userId = $request->user()?->id;

        if ($userId === null) {
            abort(401);
        }

        $merchants = Merchant::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();

        return MerchantResource::collection($merchants)->resolve();
    }
}


