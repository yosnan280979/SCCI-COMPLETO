<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescripcionSolicitud extends Model
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
        'upsize_ts'
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }

    public function moneda()
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }
}