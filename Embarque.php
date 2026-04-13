<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Embarque extends Model
{
    protected $table = 'Embarques';
    protected $primaryKey = 'Id Embarque';
    
    protected $fillable = [
        'Id SOE',
        'ETA',
        'Fecha Ent s/Ctto',
        'Fecha real arribo',
        'Fecha ent cliente',
        'Tipo Embarque',
        'upsize_ts'
    ];
    
    public $timestamps = false;
    
    public function datosSOE()
    {
        return $this->belongsTo(DatosSOE::class, 'Id SOE', 'Id SOE');
    }
    
    public function cargas()
    {
        return $this->hasMany(Carga::class, 'Id Embarque', 'Id Embarque');
    }
    
    public function facturaciones()
    {
        return $this->hasMany(Facturacion::class, 'Id embarque', 'Id Embarque');
    }
    
    public function reclamaciones()
    {
        return $this->hasMany(Reclamacion::class, 'Id embarque', 'Id Embarque');
    }
}