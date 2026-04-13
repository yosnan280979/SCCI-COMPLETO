<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCttoCubano extends Model
{
    protected $table = 'Nomenclador Tipo Ctto Cubano';
    protected $primaryKey = 'Id Tipo Ctto';
    
    protected $fillable = [
        'Tipo Ctto'
    ];
    
    public $timestamps = false;
}