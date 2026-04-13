<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamacion extends Model
{
    use HasFactory;

    protected $table = 'Reclamaciones';
    protected $primaryKey = 'Id Reclamacion';
    public $timestamps = false;

    protected $fillable = [
        'Id embarque',
        'Descripcion',
    ];

    public function embarque(): BelongsTo
    {
        return $this->belongsTo(Embarque::class, 'Id embarque', 'Id Embarque');
    }
}