<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoSuministro extends Model
{
    protected $table = 'Contratos Suministro';
    protected $primaryKey = 'Id Cttosuministro';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'No Ctto Suministro',
        'Id Cliente',
        'Descripcion',
        'Observaciones Ctto Sum',
        'upsize_ts',
    ];

    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'Id Cliente', 'Id Cliente');
    }

    // Relación inversa con DatosCttoSuministro
    public function datosCttoSuministros()
    {
        return $this->hasMany(DatosCttoSuministro::class, 'Id Cttosuministro', 'Id Cttosuministro');
    }
}