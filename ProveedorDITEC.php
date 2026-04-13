<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorDITEC extends Model
{
    use HasFactory;

    protected $table = 'Proveedor vs DITEC';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Id DITEC',
        'Id Proveedor'
    ];

    public function ditec()
    {
        return $this->belongsTo(DITEC::class, 'Id DITEC', 'Id Ditec');
    }

    public function proveedor()
    {
        return $this->belongsTo(Provider::class, 'Id Proveedor', 'Id Proveedor');
    }
}