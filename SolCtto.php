<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolCtto extends Model
{
    protected $table = 'sol ctto';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Id Solicitud',
        'Id Ctto',
        'Id SOE',
        'Observaciones',
        'Id Clasificacion',
        'Id Actualizado',
        'Valor Real Sol',
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

    // Relación con DatosSOE
    public function datosSOE()
    {
        return $this->belongsTo(DatosSOE::class, 'Id SOE', 'Id SOE');
    }

    // Accesor para obtener Id Solicitud
    public function getIdSolicitudAttribute()
    {
        return $this->attributes['Id Solicitud'] ?? null;
    }

    // Accesor para obtener Id Ctto
    public function getIdCttoAttribute()
    {
        return $this->attributes['Id Ctto'] ?? null;
    }

    // Accesor para obtener Id SOE
    public function getIdSOEAttribute()
    {
        return $this->attributes['Id SOE'] ?? null;
    }

    // Accesor para obtener Id Clasificacion
    public function getIdClasificacionAttribute()
    {
        return $this->attributes['Id Clasificacion'] ?? null;
    }

    // Accesor para obtener Id Actualizado
    public function getIdActualizadoAttribute()
    {
        return $this->attributes['Id Actualizado'] ?? null;
    }

    // Accesor para obtener Valor Real Sol
    public function getValorRealSolAttribute()
    {
        return $this->attributes['Valor Real Sol'] ?? null;
    }
}