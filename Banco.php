<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $table = 'Nomenclador de Bancos'; // CAMBIADO: de 'bancos' a 'Nomenclador de Bancos'
    protected $primaryKey = 'Id Banco';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'Id Banco',
        'Banco',
        'Id Pais'
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'Id Pais', 'Id País');
    }
}