<?php
// app/Models/PasswordResetToken.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';
    protected $primaryKey = 'Usuario';
    public $timestamps = false;
    
    protected $fillable = [
        'Usuario',
        'token',
        'created_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime'
    ];
}