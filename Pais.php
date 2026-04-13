<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pais extends Model
{
    protected $table = 'Nomenclador Paises';
    protected $primaryKey = 'Id País';   // 👈 exacto con espacio y tilde
    protected $keyType = 'int';          // usualmente entero
    public $incrementing = true;         // si es autoincrement
    public $timestamps = false;

    protected $fillable = [
        'Id País',
        'País'
    ];

    /**
     * Relación con DITEC
     */
    public function ditecs(): HasMany
    {
        return $this->hasMany(DITEC::class, 'Id País', 'Id País');
    }

    /**
     * Relación con proveedores
     */
    public function proveedores(): HasMany
    {
        return $this->hasMany(Provider::class, 'Id País', 'Id País');
    }

    /**
     * Relación muchos a muchos con proveedores y oficinas
     */
    public function proveedoresOficinas(): BelongsToMany
    {
        return $this->belongsToMany(
            Provider::class,
            'Paises vs Proveedores',
            'Id País',
            'Id Proveedor'
        )->withPivot('Oficina');
    }
}
