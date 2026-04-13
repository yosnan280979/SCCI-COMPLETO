<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OSDE extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador OSDE';
    protected $primaryKey = 'Id Osde';
    public $timestamps = false;

    protected $fillable = [
        'OSDE',
        'MICONS',
    ];

    public function balanceCenters(): HasMany
    {
        return $this->hasMany(BalanceCenter::class, 'Id OSDE', 'Id Osde');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'Id OSDE', 'Id Osde');
    }
}