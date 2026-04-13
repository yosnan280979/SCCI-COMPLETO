<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'Nomenclador Documentos';
    protected $primaryKey = 'Id Documento';
    public $timestamps = false;

    protected $fillable = [
        'Documento',
        'Imprescindible',
    ];

    protected $casts = [
        'Imprescindible' => 'boolean',
    ];
}