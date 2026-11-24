<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SpmCatatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'spm_id',
        'user_id',
        'parent_id',
        'isi',
    ];

    protected $appends = [
        'can_delete',
    ];

    public function spm(): BelongsTo
    {
        return $this->belongsTo(Spm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SpmCatatan::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SpmCatatan::class, 'parent_id')->oldest();
    }

    public function canDeleteBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isOperator() || $user->isAdmin()) {
            return true;
        }

        if ($user->id === $this->user_id) {
            // Rule: 15 menit
            return $this->created_at->diffInMinutes(now()) <= 15;
        }

        return false;
    }

    public function getCanDeleteAttribute(): bool
    {
        /** @var User|null $currentUser */
        $currentUser = auth()->user();

        return $this->canDeleteBy($currentUser);
    }
}
