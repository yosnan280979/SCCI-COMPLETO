<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'Nomenclador Clientes';
    protected $primaryKey = 'Id Cliente';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Cliente',
        'Bases Presentadas',
        'Fecha Bases',
        'OSDE',
    ];

    // Accesor para BasesPresentadas (sin espacio)
    public function getBasesPresentadasAttribute($value)
    {
        return $value;
    }

    public function setBasesPresentadasAttribute($value)
    {
        $this->attributes['Bases Presentadas'] = $value;
    }

    // Accesor para FechaBases (sin espacio)
    public function getFechaBasesAttribute($value)
    {
        return $this->attributes['Fecha Bases'] ?? null;
    }

    public function setFechaBasesAttribute($value)
    {
        $this->attributes['Fecha Bases'] = $value;
    }

    // Relación con OSDE
    public function osde()
    {
        return $this->belongsTo(OSDE::class, 'Id OSDE', 'Id Osde');
    }
}