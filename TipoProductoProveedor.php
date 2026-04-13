<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProductoProveedor extends Model
{
    protected $table = 'Tipo Producto vs Proveedor';
    public $incrementing = false;
    public $timestamps = false;

    // Clave primaria compuesta
    protected $primaryKey = ['Id Tipo Producto', 'Id Proveedor'];
    
    protected $fillable = [
        'Id Tipo Producto',
        'Id Proveedor',
        'Datos',
    ];

    // Relación con TipoProducto
    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class, 'Id Tipo Producto', 'Id Tipo Producto');
    }

    // Relación con Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id Proveedor', 'Id Proveedor');
    }
}