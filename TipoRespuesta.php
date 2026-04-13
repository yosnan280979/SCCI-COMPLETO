<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoRespuesta extends Model
{
    // Especificar el nombre exacto de la tabla con espacios
    protected $table = 'Nomenclador de Tipos de respuestas';
    
    // Clave primaria (también tiene espacios)
    protected $primaryKey = 'Id Tipo respuesta';
    
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Tipo Respuesta'
    ];

    // Opcional: Para evitar problemas con espacios en nombres de columnas
    // podemos mapear las columnas
    protected $maps = [
        'id' => 'Id Tipo respuesta',
        'tipo_respuesta' => 'Tipo Respuesta'
    ];

    // Opcional: Para acceder a las columnas como propiedades
    public function getIdAttribute()
    {
        return $this->attributes['Id Tipo respuesta'];
    }

    public function getTipoRespuestaAttribute()
    {
        return $this->attributes['Tipo Respuesta'];
    }

    public function setTipoRespuestaAttribute($value)
    {
        $this->attributes['Tipo Respuesta'] = $value;
    }
}