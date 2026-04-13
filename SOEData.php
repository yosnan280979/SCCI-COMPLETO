<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SOEData extends Model
{
    use HasFactory;

    protected $table = 'Datos SOE';
    protected $primaryKey = 'Id SOE';
    public $timestamps = false;

    protected $fillable = [
        'Id Ctto',
        'Id Solicitud',
        'No Suplemento',
        'Fecha Comite',
        'No Acta',
        'No Acuerdo',
        'Valor Mercancia',
        'Valor Ctto CUC',
        'Id Moneda',
        'Valor Mon Prov',
        'No Referencia',
        'Fecha CAD Gescons',
        'Fecha CAD MICONS',
        'No Acta MICONS',
        'Fecha firma Ctto',
        'Fecha emision certif',
        'Pendiente finan CUC',
        'Pendiente finan CUP',
        'Total embarques',
        'Observaciones Juridico',
        'Observaciones SOE',
        'Observaciones Especialista',
        'Observaciones',
        'Cancelado SOE',
        'Forma de Pago',
        'Anular Valores',
        'Id MomentoSOE',
        'Año finan',
        'Id Linea credito',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'Id Ctto', 'Id Ctto');
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'Id SOE', 'Id SOE');
    }
}