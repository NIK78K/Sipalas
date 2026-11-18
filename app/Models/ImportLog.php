<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tipe',
        'nama_file',
        'status',
        'berhasil',
        'dilewati',
        'laporan',
    ];

    protected function casts(): array
    {
        return [
            'berhasil' => 'integer',
            'dilewati' => 'integer',
            'laporan' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
