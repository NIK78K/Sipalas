<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === '1');
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['email_verified_at'] = now();

        User::create($validated);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->role !== $request->role) {
            Gate::authorize('changeRole', [$user, $request->role]);
        }

        if ($user->is_active !== $request->boolean('is_active')) {
            Gate::authorize('toggleActive', $user);
        }

        $oldRole = $user->role;
        $user->update($request->validated());

        if ($oldRole !== $user->role) {
            ActivityLog::record('perubahan_peran', $user, [
                'target_username' => $user->username,
                'old_role' => $oldRole,
                'new_role' => $user->role,
            ]);
        }

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Toggle user active status.
     */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLog::record('status_pengguna', $user, [
            'target_username' => $user->username,
            'is_active' => $user->is_active,
        ]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Pengguna {$user->username} berhasil {$statusText}.");
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $request->validate([
            'password' => ['nullable', 'string', Password::defaults()],
        ]);

        $newPassword = $request->input('password') ?: 'palas12345';
        $user->update(['password' => Hash::make($newPassword)]);

        ActivityLog::record('reset_password', $user, [
            'target_username' => $user->username,
        ]);

        return back()->with('success', "Password {$user->username} berhasil direset menjadi: {$newPassword}");
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
