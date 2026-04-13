<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'order',
        'active'
    ];
    
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'id_module');
    }
}