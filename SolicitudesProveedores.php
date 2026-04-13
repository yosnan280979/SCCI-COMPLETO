<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudesProveedores extends Model
{
    use HasFactory;

    protected $table = 'Solicitudes Proveedores';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id Solicitud',
        'Id Proveedor',
        'Respuesta',
        'Fecha Oferta',
        'Selecionado',
        'Id Ctto',
        'Observaciones',
        'Id Tipo Producto',
        'Id Tipo Respuesta',
        'Fecha Dictec',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'Id Proveedor', 'Id Proveedor');
    }
}