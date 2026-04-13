<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagosPorElCliente extends Model
{
    protected $table = 'Pagos por el Cliente';
    protected $primaryKey = 'Id';
    
    protected $fillable = [
        'Id Cttosuministro',
        'No Elemento Pago',
        'Valor MN',
        'Valor CUC',
        'Observaciones',
        'upsize_ts'
    ];
    
    public $timestamps = false;
    
    public function contratoSuministro()
    {
        return $this->belongsTo(ContratoSuministro::class, 'Id Cttosuministro', 'Id Cttosuministro');
    }
}