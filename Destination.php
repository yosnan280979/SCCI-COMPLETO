<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $table = 'Nomenclador Destinos';
    protected $primaryKey = 'Id Destino';
    
    protected $fillable = [
        'destino'
    ];
    
    public $timestamps = false;
    
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'Id Destino', 'Id Destino');
    }
}