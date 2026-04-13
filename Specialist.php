<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    protected $table = 'Nomenclador Especialistas';
    protected $primaryKey = 'Id especialista';
    public $timestamps = false;
    public $incrementing = true; // Esto es importante
    protected $keyType = 'int'; // Esto también ayuda
    
    protected $fillable = [
        'Especialista',
        'Activos',
        'Id Tutor'
    ];
    
    // Relación inversa con Solicitudes
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'Id Especialista', 'Id especialista');
    }
}