<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudDescription extends Model
{
    use HasFactory;

    protected $table = 'Descripcion Solicitud';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id Solicitud',
        'Producto',
        'UM',
        'Cantidad',
        'Precio CUC',
        'Precio Mon Prov',
        'Id Moneda',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }
}