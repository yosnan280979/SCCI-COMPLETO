<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:edit,usuarios');
    }

    public function index()
    {
        $areas = Area::where('activo', true)->orderBy('Area')->get();
        $modules = Module::orderBy('order')->get();
        
        $permissions = Permission::with(['area', 'module'])
            ->get()
            ->groupBy('area_id');
        
        return view('permissions.index', compact('areas', 'modules', 'permissions'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.area_id' => 'required|exists:areas,id',
            'permissions.*.module_id' => 'required|exists:modules,id',
        ]);

        foreach ($request->permissions as $permissionData) {
            Permission::updateOrCreate(
                [
                    'area_id' => $permissionData['area_id'],
                    'module_id' => $permissionData['module_id'],
                ],
                [
                    'view' => $permissionData['view'] ?? false,
                    'create' => $permissionData['create'] ?? false,
                    'edit' => $permissionData['edit'] ?? false,
                    'delete' => $permissionData['delete'] ?? false,
                ]
            );
        }

        return redirect()->route('permissions.index')
            ->with('success', 'Permisos actualizados correctamente');
    }

    public function resetArea($areaId)
    {
        $area = Area::findOrFail($areaId);
        
        // Resetear todos los permisos del área a false
        Permission::where('area_id', $areaId)->update([
            'view' => false,
            'create' => false,
            'edit' => false,
            'delete' => false,
        ]);

        return redirect()->route('permissions.index')
            ->with('success', "Permisos del área {$area->Area} reseteados");
    }

    public function copyFromArea(Request $request)
    {
        $request->validate([
            'source_area_id' => 'required|exists:areas,id',
            'target_area_ids' => 'required|array',
            'target_area_ids.*' => 'exists:areas,id',
        ]);

        $sourcePermissions = Permission::where('area_id', $request->source_area_id)->get();
        
        foreach ($request->target_area_ids as $targetAreaId) {
            foreach ($sourcePermissions as $sourcePermission) {
                Permission::updateOrCreate(
                    [
                        'area_id' => $targetAreaId,
                        'module_id' => $sourcePermission->module_id,
                    ],
                    [
                        'view' => $sourcePermission->view,
                        'create' => $sourcePermission->create,
                        'edit' => $sourcePermission->edit,
                        'delete' => $sourcePermission->delete,
                    ]
                );
            }
        }

        return redirect()->route('permissions.index')
            ->with('success', 'Permisos copiados correctamente');
    }
}