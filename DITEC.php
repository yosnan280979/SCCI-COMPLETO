<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DITEC extends Model
{
    protected $table = 'DITEC';
    protected $primaryKey = 'Id Ditec';   // 👈 exacto como en la BD
    protected $keyType = 'int';           // usualmente entero
    public $incrementing = true;          // si es autoincrement
    public $timestamps = false;

    protected $fillable = [
        'Id Ditec',
        'No DITEC',
        'Renueva',
        'Fecha Otorgamiento',
        'Producto',
        'Fabricante',
        'Id País',        // 👈 exacto con espacio y tilde
        'Vencido',
        'En renovacion',
        'Suministrador',
    ];

    protected $casts = [
        'Renueva'        => 'boolean',
        'Vencido'        => 'boolean',
        'En renovacion'  => 'boolean',
        'Fecha Otorgamiento' => 'date',
    ];

    /**
     * Relación con la tabla de países
     */
    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'Id País', 'Id País');
    }
}
