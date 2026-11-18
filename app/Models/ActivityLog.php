<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'aksi',
        'subjek_tipe',
        'subjek_id',
        'perubahan',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'perubahan' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity log entry.
     */
    public static function record(string $aksi, ?Model $subject = null, ?array $perubahan = null, ?int $userId = null): self
    {
        return self::create([
            'user_id' => $userId ?? Auth::id(),
            'aksi' => $aksi,
            'subjek_tipe' => $subject ? get_class($subject) : null,
            'subjek_id' => $subject?->getKey(),
            'perubahan' => $perubahan,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
