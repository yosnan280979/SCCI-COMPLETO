<?php
// app/Models/CttoCVICttoSum.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CttoCVICttoSum extends Model
{
    protected $table = 'CttoCVI vs CttoSum';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    
    protected $fillable = [
        'Id',
        'Id SOE',
        'Id Ctto Sum'
    ];
    
    // Relaciones
    public function datosSOE(): BelongsTo
    {
        return $this->belongsTo(DatosSOE::class, 'Id SOE', 'Id SOE');
    }
    
    public function contratoSuministro(): BelongsTo
    {
        return $this->belongsTo(ContratoSuministro::class, 'Id Ctto Sum', 'Id Cttosuministro');
    }
}