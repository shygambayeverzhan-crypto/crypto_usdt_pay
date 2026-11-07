<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationController extends Controller
{
    private const SESSION_ORIGINAL_USER_ID = 'impersonate_original_user_id';
    private const SESSION_FLAG = 'impersonating';

    public function start(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $currentUser || ! $currentUser->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN, __('messages.impersonation.forbidden'));
        }

        if ($currentUser->id === $user->id) {
            return back()->with('error', __('messages.impersonation.self'));
        }

        if ($user->hasRole('admin')) {
            return back()->with('error', __('messages.impersonation.admin_forbidden'));
        }

        if (! $request->session()->has(self::SESSION_ORIGINAL_USER_ID)) {
            $request->session()->put(self::SESSION_ORIGINAL_USER_ID, $currentUser->id);
        }

        $request->session()->put(self::SESSION_FLAG, true);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', __('messages.impersonation.logged_in_as', ['email' => $user->email]));
    }

    public function leave(Request $request): RedirectResponse
    {
        $originalUserId = $request->session()->pull(self::SESSION_ORIGINAL_USER_ID);
        $wasImpersonating = $request->session()->pull(self::SESSION_FLAG, false);

        if (! $wasImpersonating || ! $originalUserId) {
            return redirect()->back()->with('error', __('messages.impersonation.session_inactive'));
        }

        $adminUser = User::query()->find($originalUserId);

        if (! $adminUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', __('messages.impersonation.session_missing'));
        }

        Auth::login($adminUser);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('messages.impersonation.returned_to_admin'));
    }
}

