<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomencladorActualizar extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador de Actualizar';
    
    protected $primaryKey = 'Id Actualizado';
    
    public $incrementing = true;
    
    protected $keyType = 'int';
    
    public $timestamps = false;
    
    protected $fillable = ['Actualizado'];
}