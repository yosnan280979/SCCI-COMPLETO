<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatosBancosProveedor extends Model
{
    protected $table = 'Datos Bancos Proveedores';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id proveedor',
        'Id Banco',
        'Id Moneda',
        'Numero Cuenta',
        'Titular'
    ];

    // Relaciones correctas
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'Id proveedor', 'Id Proveedor');
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class, 'Id Banco', 'Id Banco');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }
}
