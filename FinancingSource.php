<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancingSource extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador Fuentes Financiamiento';
    protected $primaryKey = 'Id Fuentefinan';
    public $timestamps = false;

    protected $fillable = [
        'Fuente Financiamiento',
        'CT',
    ];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'Id Fuentefinan', 'Id Fuentefinan');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'Id Fuentafinan', 'Id Fuentefinan');
    }
}