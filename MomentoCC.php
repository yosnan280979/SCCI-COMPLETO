<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MomentoCC extends Model
{
    use HasFactory;

    // Especificar el nombre de la tabla
    protected $table = 'Nomenclador Momentos CC';

    // Especificar la clave primaria
    protected $primaryKey = 'Id MomentoCC';

    // No usar timestamps (created_at, updated_at)
    public $timestamps = false;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'Momento CC',
    ];

    // Opcional: Accesor para obtener la clave primaria como "id"
    public function getIdAttribute()
    {
        return $this->{'Id MomentoCC'};
    }
}