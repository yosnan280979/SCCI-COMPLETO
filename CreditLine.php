<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLine extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador Líneas Crédito';
    protected $primaryKey = 'Id Lineacredito';
    public $timestamps = false;

    protected $fillable = [
        'Linea de Crédito'
    ];

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'Id Linea Credito', 'Id Lineacredito');
    }

    public function datosSOE()
    {
        return $this->hasMany(DatosSOE::class, 'Id Linea credito', 'Id Lineacredito');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'Id Lineacredito', 'Id Lineacredito');
    }
}