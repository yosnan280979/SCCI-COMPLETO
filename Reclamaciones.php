<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reclamaciones extends Model
{
    protected $table = 'Reclamaciones';
    protected $primaryKey = 'Id';
    
    protected $fillable = [
        'Id_embarque',
        'Descripcion'
    ];
    
    public $timestamps = false;
    
    // Add relationships here if needed
}
