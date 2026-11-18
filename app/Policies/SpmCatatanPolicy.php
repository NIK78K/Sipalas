<?php

namespace App\Policies;

use App\Models\SpmCatatan;
use App\Models\User;

class SpmCatatanPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, SpmCatatan $catatan): bool
    {
        return $catatan->canDeleteBy($user);
    }
}
