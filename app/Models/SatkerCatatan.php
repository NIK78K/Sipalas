<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatkerCatatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'satker_id',
        'user_id',
        'tanggal',
        'isi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
