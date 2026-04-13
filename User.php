<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'Nomenclador Usuarios';
    protected $primaryKey = 'Usuario';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // ESTA LÍNEA ES CRÍTICA

    protected $fillable = [
        'Usuario',
        'PWD',
        'Nombre completo',
        'Id Area',
        'Id Tipo Usuario',
        'Id Especialista',
        'remember_token',
        'Activo',
    ];

    protected $hidden = [
        'PWD',
        'remember_token',
    ];

    // 🔗 Relaciones
    public function area()
    {
        return $this->belongsTo(Area::class, 'Id Area', 'Id Area');
    }

    public function userType()
    {
        return $this->belongsTo(UserType::class, 'Id Tipo Usuario', 'Id Tipo Usuario');
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class, 'Id Especialista', 'Id Especialista');
    }

    // 🔐 Permisos
    public function hasPermission($moduleSlug, $action = 'view')
    {
        if ($this->isInformatica()) {
            return true;
        }

        if (!$this->area) {
            return false;
        }

        $module = \App\Models\Module::where('slug', $moduleSlug)->first();
        if (!$module) {
            return false;
        }

        $permission = \App\Models\Permission::where('id_area', $this->area->{'Id Area'})
            ->where('id_module', $module->id)
            ->first();

        if (!$permission) {
            return false;
        }

        $columnMap = [
            'view'   => 'view',
            'create' => 'create_p',
            'edit'   => 'edit',
            'delete' => 'delete_p',
        ];

        $column = $columnMap[$action] ?? 'view';

        return (bool) $permission->{$column};
    }

    public function isInformatica()
    {
        return $this->area && $this->area->Area === 'Informatica';
    }

    public function getAllPermissions()
    {
        if (!$this->area) {
            return collect();
        }

        return \App\Models\Permission::where('id_area', $this->area->{'Id Area'})
            ->with('module')
            ->get()
            ->mapWithKeys(function ($permission) {
                return [
                    $permission->module->slug => [
                        'view'   => (bool) $permission->view,
                        'create' => (bool) $permission->create_p,
                        'edit'   => (bool) $permission->edit,
                        'delete' => (bool) $permission->delete_p,
                    ]
                ];
            });
    }

    // 🔑 Autenticación
    public function getAuthPassword()
    {
        return $this->PWD;
    }

    public function getNameAttribute()
    {
        return $this->{'Nombre completo'};
    }

    public function getAuthIdentifierName()
    {
        return 'Usuario';
    }

    public function getAuthIdentifier()
    {
        return $this->Usuario;
    }

    // 🔎 Scope
    public function scopeActive($query)
    {
        return $query->where('Activo', 1);
    }
}