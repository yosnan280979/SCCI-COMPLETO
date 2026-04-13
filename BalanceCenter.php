<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceCenter extends Model
{
    protected $table = 'Nomenclador Centros Balance';
    protected $primaryKey = 'Id Centro Balnce';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Centro Balance',
        'Activos',
        'Id OSDE',
    ];

    // Cambiado: getKey() para devolver el valor correcto
    public function getKey()
    {
        return $this->getAttribute($this->primaryKey);
    }

    // Relación con OSDE
    public function osde()
    {
        return $this->belongsTo(OSDE::class, 'Id OSDE', 'Id Osde');
    }

    // Relación inversa con Solicitudes
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'Id Centro Balance', 'Id Centro Balnce');
    }
}