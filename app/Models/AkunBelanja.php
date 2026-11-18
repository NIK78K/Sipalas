<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AkunBelanja extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function dokumenWajibs(): HasMany
    {
        return $this->hasMany(DokumenWajib::class)->orderBy('urutan');
    }

    public function activeDokumenWajibs(): HasMany
    {
        return $this->hasMany(DokumenWajib::class)
            ->where('is_active', true)
            ->orderBy('urutan');
    }

    public function spms(): HasMany
    {
        return $this->hasMany(Spm::class);
    }
}
