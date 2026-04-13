<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    protected $table = 'reclamaciones';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'embarque_id',
        'descripcion'
    ];

    protected $casts = [
        'id' => 'float',
        'embarque_id' => 'float'
    ];

    public function embarque()
    {
        return $this->belongsTo(Embarque::class);
    }

}
