<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorMarca extends Model
{
    use HasFactory;

    protected $table = 'Proveedores vs Marcas';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id proveedor',
        'Marca',
        'Productos',
        'Vencimiento'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id proveedor', 'Id Proveedor');
    }
}