<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsigPorSolic extends Model
{
    use HasFactory;

    protected $table = 'Asig por solic';
    protected $primaryKey = 'Id Solicitud';
    public $incrementing = false; // Si no es autoincremental
    public $timestamps = false;

    protected $fillable = [
        'Id Solicitud',
        'Año finan',
        'Asig MCUC',
        'upsize_ts'
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'Id Solicitud', 'Id Solicitud');
    }
}