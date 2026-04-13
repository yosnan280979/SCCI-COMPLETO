<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'Asignaciones';
    
    // Como es una clave primaria compuesta, no definimos $primaryKey
    // o podemos definirla como array si Laravel lo soporta
    protected $primaryKey = null;
    public $incrementing = false;
    
    public $timestamps = false;
    
    protected $fillable = [
        'Id Centro Balance',
        'Id Lineacredito',
        'Id Fuentafinan',
        'Año Asignacion',
        'Valor',
        'upsize_ts'
    ];
    
    // Relación con BalanceCenter (Centro de Balance)
    public function centroBalance()
    {
        return $this->belongsTo(BalanceCenter::class, 'Id Centro Balance', 'Id Centro Balnce');
    }
    
    // Relación con CreditLine (Línea de Crédito)
    public function lineaCredito()
    {
        return $this->belongsTo(CreditLine::class, 'Id Lineacredito', 'Id Lineacredito');
    }
    
    // Relación con FinancingSource (Fuente de Financiamiento)
    public function fuenteFinanciamiento()
    {
        return $this->belongsTo(FinancingSource::class, 'Id Fuentafinan', 'Id Fuentefinan');
    }
    
    // Método para obtener la clave compuesta como string
    public function getCompositeKeyAttribute()
    {
        return $this->{'Id Centro Balance'} . '-' . 
               $this->{'Id Lineacredito'} . '-' . 
               $this->{'Id Fuentafinan'} . '-' . 
               $this->{'Año Asignacion'};
    }
}