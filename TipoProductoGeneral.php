<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProductoGeneral extends Model
{
    protected $table = 'Tipo Producto General';
    protected $primaryKey = 'IdTipoprodg';
    
    protected $fillable = [
        'Tipo Prod general',
        'Grupo',
        'Arancel CUC',
        'Arancel CUP'
    ];
    
    public $timestamps = false;
    
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'Id Tipo prodg', 'IdTipoprodg');
    }
}