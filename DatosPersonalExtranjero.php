<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosPersonalExtranjero extends Model
{
    use HasFactory;

    // ESPECIFICA EL NOMBRE EXACTO DE LA TABLA CON ESPACIOS
    protected $table = 'Datos Personal  Extranjero';
    
    // ESPECIFICA LA CLAVE PRIMARIA
    protected $primaryKey = 'Id';
    
    // DESACTIVA TIMESTAMPS SI LA TABLA NO TIENE created_at y updated_at
    public $timestamps = false;

    protected $fillable = [
        'Id proveedor',
        'Funcionario extranjero',
        'Telef',
        'Email',
        'Activo',
        'Cargo',
        'Permiso de Trabajo',
        'Id pais',
        'Fecha Vencimiento',
        'Firmante',
        'Escnot',
        'Aprot',
        'Fechavenpod'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id proveedor', 'Id Proveedor');
    }
}