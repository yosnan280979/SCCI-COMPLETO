<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorSalirMercado extends Model
{
    // Ajusta el nombre de la tabla si es diferente
    protected $table = 'Proveedores para Salir al Mercado'; 
    protected $primaryKey = 'Id'; // Ajusta el nombre de la clave primaria si es diferente
    
    protected $fillable = [
        'Usuario',
        'No_Solicitud',
        'Tipo_Producto',
        'Proveedor',
        'Momento'
    ];
    
    public $timestamps = false;
    
    /**
     * Obtiene los datos adicionales del proveedor desde la tabla principal.
     * Esto permite acceder a columnas como 'Correo Cuba' y 'Telef Cuba'.
     */
    public function datosProveedor()
    {
        // Asumimos que la columna 'Proveedor' coincide con el nombre en la tabla 'Nomenclador Proveedores'
        // Asegúrate de que el modelo 'Provider' exista y apunte a 'Nomenclador Proveedores'
        return $this->belongsTo(Provider::class, 'Proveedor', 'Proveedor');
    }
}