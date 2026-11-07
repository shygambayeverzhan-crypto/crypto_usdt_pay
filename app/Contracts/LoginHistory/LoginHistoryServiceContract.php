<?php

declare(strict_types=1);

namespace App\Contracts\LoginHistory;

use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\Request;

interface LoginHistoryServiceContract
{
    public function recordLogin(User $user, ?Request $request = null): UserLoginHistory;
}

