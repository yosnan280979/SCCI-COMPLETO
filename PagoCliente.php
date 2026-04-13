<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoCliente extends Model
{
    use HasFactory;

    protected $table = 'Pagos por el Cliente';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id Cttosuministro',
        'No Elemento Pago',
        'Valor MN',
        'Valor CUC',
        'Observaciones',
        'upsize_ts'
    ];

    public function contratoSuministro()
    {
        return $this->belongsTo(ContratoSuministro::class, 'Id Cttosuministro', 'Id Cttosuministro');
    }
}