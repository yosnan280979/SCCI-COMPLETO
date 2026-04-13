<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlUsuario extends Model
{
    protected $table = 'control_usuarios';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'usuario_id',
        'entrada',
        'salida'
    ];

    protected $casts = [
        'id' => 'float',
        'entrada' => 'datetime',
        'salida' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

}
