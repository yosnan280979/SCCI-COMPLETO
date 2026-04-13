<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ministerio extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador de Ministerios';
    protected $primaryKey = 'Id Ministerio';
    public $timestamps = false;

    protected $fillable = [
        'Ministerio',
    ];
}