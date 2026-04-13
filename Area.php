<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'Nomenclador Areas';
    protected $primaryKey = 'Id Area';
    public $incrementing = true; // cámbialo a false si tu PK no es autoincremental
    protected $keyType = 'int';
    
    protected $fillable = [
        'Id Area',
        'Area',
        'Descripcion',
        'Activo'
    ];
    
    public function users()
    {
        return $this->hasMany(User::class, 'Id Area', 'Id Area');
    }
    
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'id_area', 'Id Area');
    }
}
