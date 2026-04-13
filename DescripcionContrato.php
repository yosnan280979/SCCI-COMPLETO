<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescripcionContrato extends Model
{
    protected $table = 'Descripcion Contrato';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Id Solicitud',
        'Id Ctto',
        'Producto',
        'UM',
        'Cantidad',
        'Precio CUC',
        'Precio Mon Prov',
        'Id Moneda',
    ];

    protected $casts = [
        'Id' => 'integer',
        'Id Solicitud' => 'integer',
        'Id Ctto' => 'integer',
        'Cantidad' => 'float',
        'Precio CUC' => 'float',
        'Precio Mon Prov' => 'float',
        'Id Moneda' => 'integer',
    ];

    // Relación con Solicitud
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }

    // Relación con Contrato
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'Id Ctto', 'Id Ctto');
    }

    // Relación con Moneda
    public function moneda()
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }
}