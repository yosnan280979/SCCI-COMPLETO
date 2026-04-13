<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    // Nombre correcto de la tabla
    protected $table = 'Nomenclador Clasificacion';

    // Clave primaria
    protected $primaryKey = 'Id Clasificacion';

    public $timestamps = false;

    protected $fillable = [
        'Id Clasificacion',
        'Clasificacion',
    ];
}
