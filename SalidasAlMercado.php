<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalidasAlMercado extends Model
{
    protected $table = 'Salidas al Mercado';
    protected $primaryKey = 'Id Salida Mercado';
    
    protected $fillable = [
        'No Sal Mer',
        'Id Especialista',
        'Año Sal Mer',
        'Fecha sal Mer',
        'Fecha pri oferta',
        'upsize_ts'
    ];
    
    public $timestamps = false;
    
    public function especialista()
    {
        return $this->belongsTo(Specialist::class, 'Id Especialista', 'Id especialista');
    }
}