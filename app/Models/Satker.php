<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satker extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function spms(): HasMany
    {
        return $this->hasMany(Spm::class);
    }

    public function catatans(): HasMany
    {
        return $this->hasMany(SatkerCatatan::class)->latest('tanggal');
    }
}
