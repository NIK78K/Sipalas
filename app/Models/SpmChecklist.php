<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpmChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'spm_id',
        'dokumen_wajib_id',
        'status',
        'is_checked',
        'checked_by',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function spm(): BelongsTo
    {
        return $this->belongsTo(Spm::class);
    }

    public function dokumenWajib(): BelongsTo
    {
        return $this->belongsTo(DokumenWajib::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
