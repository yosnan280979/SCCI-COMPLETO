<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpresaImportadora extends Model
{
    protected $table = 'Nomenclador de Empresas Importadoras';
    protected $primaryKey = 'Id Emp Imp';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Id Emp Imp',
        'Empresa Importadora',
        'Siglas',
        'Id Ministerio'
    ];

    // Relaciones
    public function ministerio(): BelongsTo
    {
        return $this->belongsTo(Ministerio::class, 'Id Ministerio', 'Id Ministerio');
    }

    public function proveedoresEmpImp(): HasMany
    {
        return $this->hasMany(ProveedorEmpImp::class, 'Id Emp Imp', 'Id Emp Imp');
    }
}