<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador Tipos Usuarios';
    protected $primaryKey = 'Id Tipo Usuario';
    public $timestamps = false;

    protected $fillable = [
        'Id Tipo Usuario',
        'Tipo Usuario',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'Id Tipo Usuario', 'Id Tipo Usuario');
    }
}
