<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatosPersonalCubano extends Model
{
    protected $table = 'Datos Personal Cubano';
    protected $primaryKey = 'Id';
    
    protected $fillable = [
        'Id proveedor',
        'Funcionario_cubano',
        'telef',
        'Email',
        'Carnet_Acorec',
        'Vigencia',
        'Activo',
        'Id Tipo Ctto'
    ];
    
    public $timestamps = false;
    
    /**
     * Relación con Proveedor
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'Id proveedor', 'Id Proveedor');
    }
    
    /**
     * Relación con Tipo de Contrato Cubano
     */
    public function tipoCtto(): BelongsTo
    {
        return $this->belongsTo(TipoCttoCubano::class, 'Id Tipo Ctto', 'Id Tipo Ctto');
    }
    
    /**
     * Scope para activos
     */
    public function scopeActivo($query, $activo = true)
    {
        return $query->where('Activo', $activo);
    }
}