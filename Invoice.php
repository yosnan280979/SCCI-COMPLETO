<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
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
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'Id embarque', 'Id Embarque');
    }
}