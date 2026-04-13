<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteEstatal extends Model
{
    protected $table = 'clientes_estatales';
    
    protected $fillable = [
        'ministerio',
        'nombre_empresa',
        'codigo_nit',
        'resolucion_creacion',
        'direccion',
        'telefono',
        'nombre_director',
        'resolucion_director',
        'email',
        'cuentas_bancarias',
        'convenios_firmados',
        'anno_2020',
        'anno_2021',
        'anno_2022',
        'anno_2023',
        'anno_2024',
        'anno_2025',
        'anno_2026',
    ];
}