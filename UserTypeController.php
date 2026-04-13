<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserType;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserTypeExport;
use PDF;

class UserTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }
    
    public function index(Request $request)
    {
        $query = UserType::query();
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('Tipo Usuario', 'like', "%{$search}%");
        }
        
        // Validar y establecer order_by
        $orderBy = $request->input('order_by', 'Id Tipo Usuario');
        $validOrderColumns = ['Id Tipo Usuario', 'Tipo Usuario'];
        if (!in_array($orderBy, $validOrderColumns)) {
            $orderBy = 'Id Tipo Usuario';
        }
        
        // Validar y establecer order_direction
        $orderDirection = strtolower($request->input('order_direction', 'asc'));
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        $query->orderBy($orderBy, $orderDirection);
        
        $userTypes = $query->paginate(15);
        
        return view('nomencladores.user_types.index', compact('userTypes', 'orderBy', 'orderDirection'));
    }
    
    public function create()
    {
        return view('nomencladores.user_types.create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Tipo Usuario' => 'required|string|max:50|unique:Nomenclador Tipos Usuarios,Tipo Usuario',
        ]);
        
        UserType::create($validated);
        
        return redirect()->route('nomencladores.user-types.index')
            ->with('success', 'Tipo de Usuario creado exitosamente.');
    }
    
    public function show($id)
    {
        $userType = UserType::findOrFail($id);
        
        return view('nomencladores.user_types.show', compact('userType'));
    }
    
    public function edit($id)
    {
        $userType = UserType::findOrFail($id);
        
        return view('nomencladores.user_types.edit', compact('userType'));
    }
    
    public function update(Request $request, $id)
    {
        $userType = UserType::findOrFail($id);
        
        $validated = $request->validate([
            'Tipo Usuario' => 'required|string|max:50|unique:Nomenclador Tipos Usuarios,Tipo Usuario,'.$id.',Id Tipo Usuario',
        ]);
        
        $userType->update($validated);
        
        return redirect()->route('nomencladores.user-types.show', $userType->{'Id Tipo Usuario'})
            ->with('success', 'Tipo de Usuario actualizado exitosamente.');
    }
    
    public function destroy($id)
    {
        $userType = UserType::findOrFail($id);
        
        // Verificar si hay usuarios usando este tipo
        if ($userType->users()->count() > 0) {
            return redirect()->route('nomencladores.user-types.index')
                ->with('error', 'No se puede eliminar el tipo de usuario porque tiene usuarios asignados.');
        }
        
        $userType->delete();
        
        return redirect()->route('nomencladores.user-types.index')
            ->with('success', 'Tipo de Usuario eliminado exitosamente.');
    }
    
    public function exportExcel(Request $request)
    {
        $query = UserType::query();
        
        // Aplicar filtros
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('Tipo Usuario', 'like', "%{$search}%");
        }
        
        // Orden con validación
        $orderBy = $request->input('order_by', 'Id Tipo Usuario');
        $validOrderColumns = ['Id Tipo Usuario', 'Tipo Usuario'];
        if (!in_array($orderBy, $validOrderColumns)) {
            $orderBy = 'Id Tipo Usuario';
        }
        
        $orderDirection = strtolower($request->input('order_direction', 'asc'));
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        $query->orderBy($orderBy, $orderDirection);
        
        $userTypes = $query->get();
        
        return Excel::download(new UserTypeExport($userTypes), 'tipos_usuario_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = UserType::query();
        
        // Aplicar filtros
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('Tipo Usuario', 'like', "%{$search}%");
        }
        
        // Orden con validación
        $orderBy = $request->input('order_by', 'Id Tipo Usuario');
        $validOrderColumns = ['Id Tipo Usuario', 'Tipo Usuario'];
        if (!in_array($orderBy, $validOrderColumns)) {
            $orderBy = 'Id Tipo Usuario';
        }
        
        $orderDirection = strtolower($request->input('order_direction', 'asc'));
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        $query->orderBy($orderBy, $orderDirection);
        
        $userTypes = $query->get();
        
        $pdf = PDF::loadView('nomencladores.user_types.pdf', compact('userTypes'));
        return $pdf->download('tipos_usuario_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = UserType::query();
        
        // Aplicar filtros
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('Tipo Usuario', 'like', "%{$search}%");
        }
        
        // Orden con validación
        $orderBy = $request->input('order_by', 'Id Tipo Usuario');
        $validOrderColumns = ['Id Tipo Usuario', 'Tipo Usuario'];
        if (!in_array($orderBy, $validOrderColumns)) {
            $orderBy = 'Id Tipo Usuario';
        }
        
        $orderDirection = strtolower($request->input('order_direction', 'asc'));
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        $query->orderBy($orderBy, $orderDirection);
        
        $userTypes = $query->get();
        
        return view('nomencladores.user_types.print', compact('userTypes'));
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador Tipos Usuarios,Id Tipo Usuario'
        ]);
        
        $ids = $request->selected_ids;
        
        // Verificar que no haya usuarios usando estos tipos
        foreach ($ids as $id) {
            $userType = UserType::find($id);
            if ($userType && $userType->users()->count() > 0) {
                return redirect()->route('nomencladores.user-types.index')
                    ->with('error', 'No se pueden eliminar algunos tipos de usuario porque tienen usuarios asignados.');
            }
        }
        
        $count = UserType::whereIn('Id Tipo Usuario', $ids)->delete();
        
        return redirect()->route('nomencladores.user-types.index')
            ->with('success', "Se eliminaron $count tipos de usuario correctamente.");
    }
}