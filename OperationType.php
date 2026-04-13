<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationType extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador Tipo Operacion';
    protected $primaryKey = 'Id Tipo Operacion';
    public $timestamps = false;

    protected $fillable = [
        'Tipo Operacion',
    ];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'Id Tipo Operacion', 'Id Tipo Operacion');
    }
}