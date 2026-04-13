<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorEmpImp extends Model
{
    use HasFactory;

    protected $table = 'Proveedores vs Emp Imp';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id proveedor',
        'Id Emp Imp'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id proveedor', 'Id Proveedor');
    }

    public function empresaImportadora()
    {
        return $this->belongsTo(EmpresaImportadora::class, 'Id Emp Imp', 'Id Emp Imp');
    }
}