<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoDescription extends Model
{
    protected $table = 'descripcion_contrato';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'solicitud_id',
        'contrato_id',
        'producto',
        'unidad_medida',
        'cantidad',
        'precio_cuc',
        'precio_moneda_prov',
        'moneda_id'
    ];

    protected $casts = [
        'id' => 'float',
        'solicitud_id' => 'float',
        'contrato_id' => 'float',
        'cantidad' => 'float',
        'precio_cuc' => 'float',
        'precio_moneda_prov' => 'float',
        'moneda_id' => 'float'
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function moneda()
    {
        return $this->belongsTo(Currency::class);
    }

}
