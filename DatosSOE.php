<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosSOE extends Model
{
    use HasFactory;

    protected $table = 'Datos SOE';
    protected $primaryKey = 'Id SOE';
    public $timestamps = false;

    protected $fillable = [
        'Id Ctto',
        'Id Solicitud',
        'No Suplemento',
        'Fecha Comite',
        'No Acta',
        'No Acuerdo',
        'Valor Mercancia',
        'Valor Ctto CUC',
        'Id Moneda',
        'Valor Mon Prov',
        'No Referencia',
        'Fecha CAD Gescons',
        'Fecha CAD MICONS',
        'No Acta MICONS',
        'Fecha firma Ctto',
        'Fecha emision certif',
        'Pendiente finan CUC',
        'Pendiente finan CUP',
        'Total embarques',
        'Observaciones Juridico',
        'Observaciones SOE',
        'Observaciones Especialista',
        'Observaciones',
        'Cancelado SOE',
        'Forma de Pago',
        'Anular Valores',
        'Id MomentoSOE',
        'Año finan',
        'Id Linea credito',
        'upsize_ts'
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'Id Ctto', 'Id Ctto');
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }

    public function moneda()
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }

    public function momentoSOE()
    {
        return $this->belongsTo(MomentoSOE::class, 'Id MomentoSOE', 'Id MomentoSOE');
    }

    // CORREGIR ESTA RELACIÓN - El nombre de la relación debe coincidir
    public function lineaCredito()
    {
        return $this->belongsTo(CreditLine::class, 'Id Linea credito', 'Id Lineacredito');
    }

    // O alternativamente, si prefieres usar el nombre correcto de la tabla:
    public function creditLine()
    {
        return $this->belongsTo(CreditLine::class, 'Id Linea credito', 'Id Lineacredito');
    }
}