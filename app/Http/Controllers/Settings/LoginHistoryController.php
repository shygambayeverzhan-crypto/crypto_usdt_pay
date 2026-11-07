<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserLoginHistoryResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $logins = $user
            ->loginHistories()
            ->latest()
            ->paginate(20)
            ->through(fn ($login) => (new UserLoginHistoryResource($login))->resolve());

        return $this->inertia('settings/LoginHistory', [
            'logins' => $logins,
        ]);
    }
}

