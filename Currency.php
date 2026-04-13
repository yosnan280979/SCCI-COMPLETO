<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'Nomenclador Monedas';
    protected $primaryKey = 'Id Moneda';
    public $timestamps = false;
    
    protected $fillable = [
        'Id Moneda',
        'Moneda'
    ];
}