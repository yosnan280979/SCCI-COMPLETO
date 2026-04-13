<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProveedor extends Model
{
    protected $table = 'Nomenclador Tipo Proveedor';
    protected $primaryKey = 'Id Tipo Prov';
    public $timestamps = false;
    
    protected $fillable = [
        'Id Tipo Prov',
        'Tipo Proveedor'
    ];
}