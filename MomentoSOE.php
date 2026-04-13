<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MomentoSOE extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador de Momentos SOE';
    protected $primaryKey = 'Id MomentoSOE'; // Especifica la clave primaria correcta
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'Momento SOE'
    ];
}