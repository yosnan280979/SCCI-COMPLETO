<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capacidad extends Model
{
    protected $table = 'Nomenclador Capacidades'; // CAMBIADO: de 'capacidades' a 'Nomenclador Capacidades'
    protected $primaryKey = 'Id Capacidad';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'Id Capacidad',
        'Capacidad',
        'Descripcion'
    ];
}