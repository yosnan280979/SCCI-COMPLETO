<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    
    // Usamos 'create_p' y 'delete_p' porque 'create' y 'delete' son palabras reservadas en MySQL
    protected $fillable = [
        'id_area',
        'id_module',
        'view',
        'create_p',
        'edit',
        'delete_p'
    ];
    
    protected $casts = [
        'view' => 'boolean',
        'create_p' => 'boolean',
        'edit' => 'boolean',
        'delete_p' => 'boolean'
    ];
    
    // Métodos de acceso para mantener compatibilidad
    public function getCreateAttribute()
    {
        return $this->create_p;
    }
    
    public function getDeleteAttribute()
    {
        return $this->delete_p;
    }
    
    // Relación con el área
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area', 'Id Area');
    }
    
    // Relación con el módulo
    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }
}