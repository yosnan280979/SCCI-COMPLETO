<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'Solicitudes';
    protected $primaryKey = 'Id Solicitud';
    public $timestamps = false;
    
    protected $fillable = [
        'No Solicitud',
        'Fecha rec sol',
        'Fecha acep aduana',
        'Fecha acep esp',
        'Fecha Solicitud',
        'Decripción Solicitud',
        'Asignado MCUC',
        'Distribuido MCUC',
        'Fecha Salida Mercado',
        'Fecha primera oferta',
        'Id Especialista',
        'Id Centro Balance',
        'Id OSDE',
        'Id Cliente',
        'Id Destino',
        'Id Clasificacion',
        'Id Tipo Operacion',
        'Id Linea Credito',
        'Id Fuentefinan',
        'Id Tipo prodg',
        'Dias Oferta',
        'añosol',
        'Observ Esp',
        'Devuelta',
        'Fecha Dev',
        'Cancelada',
        'Fecha Can',
        'añofinan',
        'Pendiente Contratar',
        'Contratado real CUC',
        'Observaciones',
        'act',
        'Fecha entact',
        'fecha salact',
        'No correo',
        'Añocorreo',
        'pie',
        'Fecha Sol pie',
        'Fecha aprob pie',
        'Progexp',
        'Ditec',
        'Permiso esp',
        'Fecha ultimo Dictamen',
        'upsize_ts'
    ];
    
    // Relación con BalanceCenter (Centro de Balance)
    public function balanceCenter()
    {
        return $this->belongsTo(BalanceCenter::class, 'Id Centro Balance', 'Id Centro Balnce');
    }
    
    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'Id Cliente', 'Id Cliente');
    }
    
    // Relación con Specialist (Especialista)
    public function especialista()
    {
        return $this->belongsTo(Specialist::class, 'Id Especialista', 'Id especialista');
    }
    
    // Relación con OSDE
    public function osde()
    {
        return $this->belongsTo(OSDE::class, 'Id OSDE', 'Id Osde');
    }
    
    // Relación con las descripciones de productos
    public function descripciones()
    {
        return $this->hasMany(DescripcionSolicitud::class, 'Id Solicitud', 'Id Solicitud');
    }
    
    // Relación con Clasificacion
    public function clasificacion()
    {
        return $this->belongsTo(Clasification::class, 'Id Clasificacion', 'Id Clasificacion');
    }
    
    // Relación con TipoOperacion
    public function tipoOperacion()
    {
        return $this->belongsTo(OperationType::class, 'Id Tipo Operacion', 'Id Tipo Operacion');
    }
    
    // Relación con LineaCredito
    public function lineaCredito()
    {
        return $this->belongsTo(CreditLine::class, 'Id Linea Credito', 'Id Lineacredito');
    }
    
    // Relación con FuenteFinanciamiento
    public function fuenteFinanciamiento()
    {
        return $this->belongsTo(FinancingSource::class, 'Id Fuentefinan', 'Id Fuentefinan');
    }
    
    // Relación con TipoProductoGeneral
    public function tipoProductoGeneral()
    {
        return $this->belongsTo(TipoProductoGeneral::class, 'Id Tipo prodg', 'IdTipoprodg');
    }
    
    // Relación con Destino
    public function destino()
    {
        return $this->belongsTo(Destination::class, 'Id Destino', 'Id Destino');
    }
    
    // Relación con Proveedores (a través de la tabla intermedia Solicitudes Proveedores)
    public function proveedoresAsociados()
    {
        return $this->belongsToMany(Provider::class, 'Solicitudes Proveedores', 'Id Solicitud', 'Id Proveedor')
            ->withPivot([
                'Respuesta', 
                'Fecha Oferta', 
                'Selecionado', 
                'Observaciones', 
                'Id Tipo Producto',
                'Id Tipo Respuesta',
                'Fecha Dictec'
            ]);
    }
    
    // Relación con Contratos (a través de la tabla intermedia sol ctto)
    public function contratosAsociados()
    {
        return $this->belongsToMany(Contrato::class, 'sol ctto', 'Id Solicitud', 'Id Ctto')
            ->withPivot([
                'Observaciones',
                'Id Clasificacion',
                'Id Actualizado',
                'Valor Real Sol',
                'upsize_ts'
            ]);
    }
    
    // Relación con Contratos (directa - alternativa)
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'Id Solicitud', 'Id Solicitud');
    }
    
    // Relación con SolicitudesProveedores (intermedia)
    public function solicitudesProveedores()
    {
        return $this->hasMany(SolicitudProveedor::class, 'Id Solicitud', 'Id Solicitud');
    }
    
    // Relación con DescripcionContrato
    public function descripcionesContrato()
    {
        return $this->hasMany(DescripcionContrato::class, 'Id Solicitud', 'Id Solicitud');
    }
    
    // Scope para solicitudes activas
    public function scopeActivas($query)
    {
        return $query->where('Cancelada', 0)->where('Devuelta', 0);
    }
    
    // Scope para solicitudes canceladas
    public function scopeCanceladas($query)
    {
        return $query->where('Cancelada', 1);
    }
    
    // Scope para solicitudes devueltas
    public function scopeDevueltas($query)
    {
        return $query->where('Devuelta', 1);
    }
    
    // Método para obtener el estado de la solicitud
    public function getEstadoAttribute()
    {
        if ($this->Cancelada) {
            return 'Cancelada';
        }
        
        if ($this->Devuelta) {
            return 'Devuelta';
        }
        
        if ($this->{'Pendiente Contratar'}) {
            return 'Pendiente de Contratar';
        }
        
        if ($this->{'Contratado real CUC'} > 0) {
            return 'Contratada';
        }
        
        return 'En Proceso';
    }
    
    // Método para obtener el nombre completo del especialista
    public function getNombreEspecialistaAttribute()
    {
        return $this->especialista ? $this->especialista->Especialista : 'No asignado';
    }
    
    // Método para obtener el nombre del cliente
    public function getNombreClienteAttribute()
    {
        return $this->cliente ? $this->cliente->Cliente : 'No asignado';
    }
    
    // Método para obtener el nombre del centro de balance
    public function getNombreCentroBalanceAttribute()
    {
        return $this->balanceCenter ? $this->balanceCenter->{'Centro Balance'} : 'No asignado';
    }
}