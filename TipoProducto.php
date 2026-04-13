<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProducto extends Model
{
    protected $table = 'Nomenclador Tipos Productos';
    protected $primaryKey = 'Id Tipo Producto';
    
    protected $fillable = [
        'Tipo Producto',
        'Siglas',
        'Id tipo prodg'
    ];
    
    public $timestamps = false;
    
    public function tipoProductoGeneral()
    {
        return $this->belongsTo(TipoProductoGeneral::class, 'Id tipo prodg', 'IdTipoprodg');
    }
    
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'Id Tipo prodg', 'Id tipo prodg');
    }
}