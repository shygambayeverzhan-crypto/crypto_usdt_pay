<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserApprovalRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UserFilterRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UsersController extends Controller
{
    public function index(UserFilterRequest $request): Response
    {
        $filters = $request->filters();

        $paginator = User::query()
            ->with('roles:id,name')
            ->when($filters['role'], function (Builder $query, string $role) {
                $query->whereHas('roles', fn (Builder $q) => $q->where('name', $role));
            })
            ->when($filters['search'], function (Builder $query, string $search) {
                $term = '%' . $search . '%';
                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('id', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => (new UserResource($user))->resolve());

        return $this->inertia('admin/users/Index', [
            'users' => $paginator,
            'roleOptions' => [
                ['value' => 'admin', 'label' => __('messages.users.roles.admin')],
                ['value' => 'user', 'label' => __('messages.users.roles.user')],
            ],
            'currentUserId' => Auth::id(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreUserRequest $request, CreatesNewUsers $creator): JsonResponse|RedirectResponse
    {
        $payload = $request->validated();

        $user = $creator->create(Arr::only($payload, [
            'name',
            'email',
            'password',
            'password_confirmation',
        ]));

        $user->approved_at = now();
        $user->save();

        $roleName = $payload['role'] ?? 'user';
        $role = $this->resolveRole($roleName);
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        event(new Registered($user));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('messages.users.created'),
                'user' => (new UserResource($user))->resolve(),
            ], HttpResponse::HTTP_CREATED);
        }

        return back()->with('success', __('messages.users.created'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        $payload = $request->validated();

        $user->fill(Arr::only($payload, ['name', 'email']));

        if (!empty($payload['password'])) {
            $user->password = $payload['password'];
        }

        $user->save();

        if (isset($payload['role'])) {
            if ($user->id === $request->user()?->id) {
                abort(HttpResponse::HTTP_FORBIDDEN, __('messages.users.cannot_change_own_role'));
            }
            $role = $this->resolveRole($payload['role']);
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('messages.users.updated'),
                'user' => (new UserResource($user->fresh('roles')))->resolve(),
            ]);
        }

        return back()->with('success', __('messages.users.updated'));
    }

    public function updateApproval(UpdateUserApprovalRequest $request, User $user): JsonResponse|RedirectResponse
    {
        $approved = (bool) $request->validated('approved');

        if ($user->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        if ($approved) {
            $user->approved_at = $user->approved_at ?? now();
        } else {
            $user->approved_at = null;
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('messages.users.updated'),
                'user' => (new UserResource($user->fresh('roles')))->resolve(),
            ]);
        }

        return back()->with('success', __('messages.users.updated'));
    }

    private function resolveRole(string $name): ?Role
    {
        if (!in_array($name, ['admin', 'user'], true)) {
            return null;
        }

        return Role::query()->firstOrCreate(
            ['name' => $name],
            [
                'display_name' => $name === 'admin' ? __('messages.users.roles.admin') : __('messages.users.roles.user'),
                'description' => $name === 'admin'
                    ? __('messages.users.roles.admin_description')
                    : __('messages.users.roles.user_description'),
            ]
        );
    }
}


