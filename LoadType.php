<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoadType extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador Tipo Carga';
    protected $primaryKey = 'Id Tipo Carga';
    public $timestamps = false;

    protected $fillable = [
        'Tipo Carga',
    ];

    public function cargas(): HasMany
    {
        return $this->hasMany(Carga::class, 'Id Tipo Carga', 'Id Tipo Carga');
    }
}