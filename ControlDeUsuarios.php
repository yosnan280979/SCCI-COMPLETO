<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlDeUsuarios extends Model
{
    protected $table = 'Control de Usuarios';
    protected $primaryKey = 'Id';
    
    protected $fillable = [
        'PDW',
        'Entrada',
        'Salida'
    ];
    
    public $timestamps = false;
    
    // Add relationships here if needed
}
