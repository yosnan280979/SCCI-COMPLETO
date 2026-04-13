<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facturacion extends Model
{
    use HasFactory;

    protected $table = 'Facturacion';
    protected $primaryKey = 'Id Facturacion';
    public $timestamps = false;

    protected $fillable = [
        'Id embarque',
        'No Factura',
        'Fecha Factura',
        'Valor CUC',
        'Valor CUP',
        'upsize_ts'
    ];

    public function embarque()
    {
        return $this->belongsTo(Embarque::class, 'Id embarque', 'Id Embarque');
    }
}