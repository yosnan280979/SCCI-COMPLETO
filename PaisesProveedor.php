<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaisesProveedor extends Model
{
    use HasFactory;

    protected $table = 'Paises vs Proveedores';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id proveedor',
        'Id Pais',
        'Oficina'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id proveedor', 'Id Proveedor');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'Id Pais', 'Id País');
    }
}