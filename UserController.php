<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserType;
use App\Models\Area;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use PDF;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['userType', 'area', 'specialist']);
        
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Usuario', 'like', "%{$search}%")
                  ->orWhere('Nombre completo', 'like', "%{$search}%");
            });
        }
        
        // Filtro por tipo de usuario
        if ($request->filled('filter_tipo')) {
            $query->where('Id Tipo Usuario', $request->filter_tipo);
        }
        
        // Filtro por área
        if ($request->filled('filter_area')) {
            $query->where('Id Area', $request->filter_area);
        }
        
        // Filtro por especialista
        if ($request->filled('filter_especialista')) {
            $query->where('Id Especialista', $request->filter_especialista);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Usuario');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar columnas de ordenación
        $validColumns = ['Usuario', 'Nombre completo', 'Activo'];
        if (!in_array($sortBy, $validColumns)) {
            $sortBy = 'Usuario';
        }
        
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $users = $query->paginate(20)->appends($request->query());
        
        $userTypes = UserType::all();
        $areas = Area::all();
        $specialists = Specialist::where('Activos', 1)->get();
        
        return view('users.index', compact('users', 'userTypes', 'areas', 'specialists'));
    }

    public function create()
    {
        $userTypes = UserType::all();
        $areas = Area::all();
        $specialists = Specialist::where('Activos', 1)->get();
        
        return view('users.create', compact('userTypes', 'areas', 'specialists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Usuario' => 'required|string|max:50|unique:Nomenclador Usuarios,Usuario',
            'password' => 'required|string|min:6',
            'nombre_completo' => 'required|string|max:255',
            'id_tipo_usuario' => 'required|exists:Nomenclador Tipos Usuarios,Id Tipo Usuario',
            'id_area' => 'nullable|exists:Nomenclador Areas,Id Area',
            'id_especialista' => 'nullable|exists:Nomenclador Especialistas,Id especialista',
            'Activo' => 'required|boolean',
        ]);
        
        // Crear usuario con contraseña encriptada
        User::create([
            'Usuario' => $request->Usuario,
            'PWD' => Hash::make($request->password),
            'Id Tipo Usuario' => $request->id_tipo_usuario,
            'Id Area' => $request->id_area,
            'Id Especialista' => $request->id_especialista,
            'Nombre completo' => $request->nombre_completo,
            'Activo' => $request->Activo,
        ]);
        
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function show($usuario)
    {
        // Buscar por Usuario (nombre de usuario)
        $user = User::findOrFail($usuario);
        return view('users.show', compact('user'));
    }

    public function edit($usuario)
    {
        // Buscar por Usuario (nombre de usuario)
        $user = User::findOrFail($usuario);
        $userTypes = UserType::all();
        $areas = Area::all();
        $specialists = Specialist::where('Activos', 1)->get();
        
        return view('users.edit', compact('user', 'userTypes', 'areas', 'specialists'));
    }

    public function update(Request $request, $usuario)
    {
        // Buscar por Usuario (nombre de usuario)
        $user = User::findOrFail($usuario);
        
        $request->validate([
            'Usuario' => 'required|string|max:50|unique:Nomenclador Usuarios,Usuario,' . $user->Usuario . ',Usuario',
            'password' => 'nullable|string|min:6',
            'nombre_completo' => 'required|string|max:255',
            'id_tipo_usuario' => 'required|exists:Nomenclador Tipos Usuarios,Id Tipo Usuario',
            'id_area' => 'nullable|exists:Nomenclador Areas,Id Area',
            'id_especialista' => 'nullable|exists:Nomenclador Especialistas,Id especialista',
            'Activo' => 'required|boolean',
        ]);
        
        $data = [
            'Usuario' => $request->Usuario,
            'Id Tipo Usuario' => $request->id_tipo_usuario,
            'Id Area' => $request->id_area,
            'Id Especialista' => $request->id_especialista,
            'Nombre completo' => $request->nombre_completo,
            'Activo' => $request->Activo,
        ];
        
        // Actualizar contraseña si se proporcionó
        if ($request->filled('password')) {
            $data['PWD'] = Hash::make($request->password);
        }
        
        $user->update($data);
        
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy($usuario)
    {
        // Buscar por Usuario (nombre de usuario)
        $user = User::findOrFail($usuario);
        $user->delete();
        
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
    
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron usuarios para eliminar.');
        }
        
        $count = User::whereIn('Usuario', $ids)->delete();
        
        return redirect()->route('usuarios.index')
            ->with('success', "Se eliminaron $count usuarios correctamente.");
    }
    
    public function exportExcel(Request $request)
    {
        $query = User::with(['userType', 'area', 'specialist']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Usuario', 'like', "%{$search}%")
                  ->orWhere('Nombre completo', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('filter_tipo')) {
            $query->where('Id Tipo Usuario', $request->filter_tipo);
        }
        
        if ($request->filled('filter_area')) {
            $query->where('Id Area', $request->filter_area);
        }
        
        if ($request->filled('filter_especialista')) {
            $query->where('Id Especialista', $request->filter_especialista);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Usuario', $ids);
            }
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Usuario');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar columnas de ordenación
        $validColumns = ['Usuario', 'Nombre completo', 'Activo'];
        if (!in_array($sortBy, $validColumns)) {
            $sortBy = 'Usuario';
        }
        
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $users = $query->get();
        
        return Excel::download(new UsersExport($users), 'usuarios_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = User::with(['userType', 'area', 'specialist']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Usuario', 'like', "%{$search}%")
                  ->orWhere('Nombre completo', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('filter_tipo')) {
            $query->where('Id Tipo Usuario', $request->filter_tipo);
        }
        
        if ($request->filled('filter_area')) {
            $query->where('Id Area', $request->filter_area);
        }
        
        if ($request->filled('filter_especialista')) {
            $query->where('Id Especialista', $request->filter_especialista);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Usuario', $ids);
            }
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Usuario');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar columnas de ordenación
        $validColumns = ['Usuario', 'Nombre completo', 'Activo'];
        if (!in_array($sortBy, $validColumns)) {
            $sortBy = 'Usuario';
        }
        
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $users = $query->get();
        
        $pdf = PDF::loadView('users.pdf', [
            'users' => $users,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('usuarios_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = User::with(['userType', 'area', 'specialist']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Usuario', 'like', "%{$search}%")
                  ->orWhere('Nombre completo', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('filter_tipo')) {
            $query->where('Id Tipo Usuario', $request->filter_tipo);
        }
        
        if ($request->filled('filter_area')) {
            $query->where('Id Area', $request->filter_area);
        }
        
        if ($request->filled('filter_especialista')) {
            $query->where('Id Especialista', $request->filter_especialista);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Usuario', $ids);
            }
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Usuario');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar columnas de ordenación
        $validColumns = ['Usuario', 'Nombre completo', 'Activo'];
        if (!in_array($sortBy, $validColumns)) {
            $sortBy = 'Usuario';
        }
        
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $users = $query->get();
        
        return view('users.print', [
            'users' => $users,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('filter_tipo')) {
            $userType = UserType::find($request->filter_tipo);
            $filtros[] = "Tipo Usuario: " . ($userType ? $userType->{'Tipo Usuario'} : $request->filter_tipo);
        }
        
        if ($request->filled('filter_area')) {
            $area = Area::find($request->filter_area);
            $filtros[] = "Área: " . ($area ? $area->{'Area'} : $request->filter_area);
        }
        
        if ($request->filled('filter_especialista')) {
            $specialist = Specialist::find($request->filter_especialista);
            $filtros[] = "Especialista: " . ($specialist ? $specialist->{'Especialista'} : $request->filter_especialista);
        }
        
        return $filtros;
    }
}