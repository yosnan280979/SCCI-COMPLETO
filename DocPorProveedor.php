<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocPorProveedor extends Model
{
    use HasFactory;

    protected $table = 'Doc por Proveedor';
    protected $primaryKey = 'Id Proveedor'; // Esta tabla tiene clave compuesta, así que usamos solo una
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Id Proveedor',
        'Id Documento',
        'Caduca',
        'Fecha Caducidad'
    ];

    // Método para obtener el ID correcto (puede que necesites esto para rutas)
    public function getRouteKeyName()
    {
        return 'Id Proveedor';
    }

    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id Proveedor', 'Id Proveedor');
    }

    public function documento()
    {
        return $this->belongsTo(Document::class, 'Id Documento', 'Id Documento');
    }
}