<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenWajib extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'akun_belanja_id',
        'nama',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function akunBelanja(): BelongsTo
    {
        return $this->belongsTo(AkunBelanja::class);
    }
}
