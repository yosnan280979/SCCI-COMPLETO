<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosCC extends Model
{
    use HasFactory;

    protected $table = 'Datos CC';
    protected $primaryKey = 'Id CC';
    public $timestamps = false;

    protected $fillable = [
        'Id CC',
        'Id SOE',
        'No CC',
        'Id Moneda',
        'Valor CC',
        'Id Capacidad',
        'Fecha Pedido Cap',
        'Fecha Asignada Cap',
        'Fecha Presentada CC',
        'Fecha Apertura CC',
        'Observaciones CC',
        'Id MomentoCC',
        'Año finan',
        'upsize_ts',
        'Id Linea credito'
    ];

    public function datosSOE()
    {
        return $this->belongsTo(DatosSOE::class, 'Id SOE', 'Id SOE');
    }

    public function moneda()
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }

    public function capacidad()
    {
        return $this->belongsTo(Capacidad::class, 'Id Capacidad', 'Id Capacidad');
    }

    public function momentoCC()
    {
        return $this->belongsTo(MomentoCC::class, 'Id MomentoCC', 'Id MomentoCC');
    }

    // CORREGIDO: Cambiar de creditLine a lineaCredito
    public function lineaCredito()
    {
        return $this->belongsTo(CreditLine::class, 'Id Linea credito', 'Id Lineacredito');
    }
}