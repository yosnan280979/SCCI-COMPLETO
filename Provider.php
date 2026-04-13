<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'Nomenclador Proveedores';
    protected $primaryKey = 'Id Proveedor';
    public $timestamps = false;
    
    protected $fillable = [
        'No Exp',
        'Proveedor',
        'País',
        'Activo',
        'Correo',
        'Codigo MINCEX',
        'Fecha CC',
        'Id Tipo Prov',
        'Oficina Cuba',
        'Correo Cuba',
        'Direccion Cuba',
        'Direccion Cmatriz',
        'Correo Cmatriz',
        'Telef Cuba',
        'Telef Cmatriz',
        'Fax Cuba',
        'Fax Cmatriz',
        'Productos',
        'Vigencia CC',
        'No registro CC',
        'Siglas',
        'Logo',
        'Sitio Web',
        'Fecha Fundacion',
        'Capital Social',
        'Id Moneda',
        'Codigo Postal Cmatriz',
        'Codigo Postal Cuba',
        'Registro Mercantil',
        'Fecha RM',
        'Fecha V RM',
        'Region Cmatriz',
        'Ciudad cmatriz',
        'No Esc Cons',
        'Fecha Esc Cons',
        'Notario',
        'Fecha confa1',
        'Fecha aval BCuba',
        'Fecha aval Bext',
        'Año ultbalance',
        'Fecha alta comite',
        'Acta alta comite',
        'Acuerdo alta comite',
        'Fecha alta consejo',
        'Acta alta consejo',
        'Acuerdo alta consejo',
        'Fecha baja consejo',
        'Acta baja consejo',
        'Acuerdo baja consejo',
        'temp Observ',
        'upsize_ts',
        'Id Tipo Producto',
    ];
    
    // Relación con TipoProducto
    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class, 'Id Tipo Producto', 'Id Tipo Producto');
    }
    
    // Relación con Contratos
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'Id Proveedor', 'Id Proveedor');
    }
    
    // Relación con Pais (para compatibilidad con diferentes nombres)
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'País', 'Id País');
    }
    
    // Alias para compatibilidad
    public function paisRelacion()
    {
        return $this->belongsTo(Pais::class, 'País', 'Id País');
    }
    
    // Relación con TipoProveedor
    public function tipoProveedor()
    {
        return $this->belongsTo(TipoProveedor::class, 'Id Tipo Prov', 'Id Tipo Prov');
    }
    
    // Alias para compatibilidad
    public function tipoProveedorRelacion()
    {
        return $this->belongsTo(TipoProveedor::class, 'Id Tipo Prov', 'Id Tipo Prov');
    }
    
    // Relación con Moneda (Currency)
    public function moneda()
    {
        return $this->belongsTo(Currency::class, 'Id Moneda', 'Id Moneda');
    }

    // Relación inversa con marcas
    public function marcas()
    {
        return $this->hasMany(ProveedorMarca::class, 'Id proveedor', 'Id Proveedor');
    }
}