<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomencladorTiposUsuarios extends Model
{
    protected $table = 'Nomenclador Tipos Usuarios';
    protected $primaryKey = 'Id Tipo Usuario';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'Tipo Usuario'
    ];

    /**
     * Relación con usuarios
     */
    public function users()
    {
        return $this->hasMany('App\Models\User', 'Id Tipo Usuario', 'Id Tipo Usuario');
    }
}