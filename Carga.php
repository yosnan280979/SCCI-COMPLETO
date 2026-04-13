<?php
// app/Models/Carga.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Carga extends Model
{
    protected $table = 'Cargas';
    protected $primaryKey = 'Id Tipo Carga';
    public $timestamps = false;
    
    protected $fillable = [
        'Id Tipo Carga',
        'Id Embarque',
        'Cantidad',
        'Real',
        'upsize_ts'
    ];
    
    protected $casts = [
        'Cantidad' => 'float',
        'Real' => 'float',
        'upsize_ts' => 'binary'
    ];
    
    // Relaciones
    public function tipoCarga(): BelongsTo
    {
        return $this->belongsTo(LoadType::class, 'Id Tipo Carga', 'Id Tipo Carga');
    }
    
    public function embarque(): BelongsTo
    {
        return $this->belongsTo(Embarque::class, 'Id Embarque', 'Id Embarque');
    }
}