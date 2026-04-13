<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatosCttoSuministro extends Model
{
    protected $table = 'Datos Ctto suministro';
    protected $primaryKey = 'Id Supsum';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Id Cttosuministro',
        'No Suplemento',
        'Fecha firma Ctto',
        'Importe CUC',
        'Importe CUP',
        'Pendiente Finan CUC',
        'Pendiente CUP',
        'Forma de Pago',
        'upsize_ts',
    ];

    protected $casts = [
        'Importe CUC' => 'float',
        'Importe CUP' => 'float',
        'Pendiente Finan CUC' => 'float',
        'Pendiente CUP' => 'float',
        'Fecha firma Ctto' => 'datetime',
        'upsize_ts' => 'datetime',
    ];

    // Relación con ContratoSuministro
    public function contratoSuministro()
    {
        return $this->belongsTo(ContratoSuministro::class, 'Id Cttosuministro', 'Id Cttosuministro');
    }
}