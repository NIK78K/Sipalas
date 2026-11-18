<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOperator();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isOperator();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isOperator();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): Response
    {
        if (! $user->isOperator()) {
            return Response::deny('Hanya Operator yang dapat mengelola pengguna.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can toggle active status of the target user.
     */
    public function toggleActive(User $user, User $model): Response
    {
        if (! $user->isOperator()) {
            return Response::deny('Hanya Operator yang dapat mengubah status pengguna.');
        }

        if ($user->id === $model->id && $model->isOperator()) {
            $operatorCount = User::where('role', 'operator')->where('is_active', true)->count();
            if ($operatorCount <= 1) {
                return Response::deny('Operator tunggal tidak boleh menonaktifkan diri sendiri.');
            }
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can change role of the target user.
     */
    public function changeRole(User $user, User $model, string $newRole): Response
    {
        if (! $user->isOperator()) {
            return Response::deny('Hanya Operator yang dapat mengubah peran pengguna.');
        }

        if ($user->id === $model->id && $model->isOperator() && $newRole !== 'operator') {
            $operatorCount = User::where('role', 'operator')->where('is_active', true)->count();
            if ($operatorCount <= 1) {
                return Response::deny('Operator tunggal tidak boleh menurunkan peran sendiri.');
            }
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can reset password of the target user.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->isOperator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): Response
    {
        if (! $user->isOperator()) {
            return Response::deny('Hanya Operator yang dapat menghapus pengguna.');
        }

        if ($user->id === $model->id) {
            return Response::deny('Tidak dapat menghapus akun sendiri.');
        }

        return Response::allow();
    }
}
