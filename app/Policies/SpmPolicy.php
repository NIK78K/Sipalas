<?php

namespace App\Policies;

use App\Models\Spm;
use App\Models\User;

class SpmPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Spm $spm): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canExamine();
    }

    public function update(User $user, Spm $spm): bool
    {
        return $user->canExamine();
    }

    public function examine(User $user, Spm $spm): bool
    {
        return $user->canExamine();
    }

    public function delete(User $user, Spm $spm): bool
    {
        return $user->canExamine();
    }
}
