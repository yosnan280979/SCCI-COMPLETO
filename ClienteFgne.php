<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteFgne extends Model
{
    protected $table = 'clientes_fgne';
    
    protected $fillable = [
        'no_registro',
        'fgne_codigo',          // Código FGNE (TCP/CNA/MIPYME)
        'nombre',
        'codigo_nit',
        'objeto_social',
        'direccion',
        'telefonos',
        'email',
        'cuenta_mn',
        'cuenta_usd',
        'cuenta_mlc',
        'sucursal_banco',
        'representacion',
        'ficha',
        'actualiz',
        'bases_generales',
        'fecha_actualizacion',
        'ctto_consignacion',
        'fecha_ctto_consignacion',
        'ctto_in_bond',
        'fecha_ctto_in_bond',
    ];
}
