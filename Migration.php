<?php
// app/Models/Migration.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Migration extends Model
{
    protected $table = 'migrations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'migration',
        'batch'
    ];
}