<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $table = 'Contratos';
    protected $primaryKey = 'Id Ctto';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'No Ctto',
        'Id Proveedor',
        'Valor Ctto Mon Prov',
        'Id Moneda',
        'Forma de Pago',
        'Valor Ctto CUC',
        'Concluido',
        'Observaciones Esp',
        'Cancelado',
    ];

    protected $casts = [
        'Concluido' => 'boolean',
        'Cancelado' => 'boolean',
        'Valor Ctto Mon Prov' => 'float',
        'Valor Ctto CUC' => 'float',
    ];

    // Relación con Proveedor
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'Id Proveedor', 'Id Proveedor');
    }

    // Relación con Moneda
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }
}