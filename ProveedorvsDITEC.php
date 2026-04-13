<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorvsDITEC extends Model
{
    protected $table = 'Proveedor vs DITEC';
    protected $primaryKey = 'Id';
    
    protected $fillable = [
        'Id_DITEC',
        'Id_Proveedor'
    ];
    
    public $timestamps = false;
    
    // Add relationships here if needed
}
