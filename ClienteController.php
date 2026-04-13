<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\OSDE;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientesExport;
use PDF;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();
        
        // Filtros
        if ($request->filled('search')) {
            $query->where('Cliente', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('bases_presentadas')) {
            $query->where('Bases Presentadas', $request->bases_presentadas);
        }
        
        if ($request->filled('osde_id')) {
            $query->where('Id OSDE', $request->osde_id);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->where('Fecha Bases', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->where('Fecha Bases', '<=', $request->fecha_hasta);
        }
        
        // Ordenación con validación
        $sortBy = $request->input('sort_by', 'Cliente');
        $sortDir = strtolower($request->input('sort_dir', 'asc'));
        
        // Validar que sort_dir sea 'asc' o 'desc'
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        // Validar que sort_by no esté vacío y sea una columna válida
        $validSortColumns = ['Id Cliente', 'Cliente', 'Bases Presentadas', 'Fecha Bases', 'Id OSDE'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Cliente';
        }
        
        $query->orderBy($sortBy, $sortDir);

        // Cargar relación con OSDE
        $query->with('osde');
        
        // Paginación con preservación de parámetros
        $clientes = $query->paginate(20)->appends($request->query());
        
        // Obtener todos los OSDEs para el filtro
        $osdes = OSDE::orderBy('OSDE')->get();

        return view('nomencladores.clientes.index', compact('clientes', 'osdes'));
    }

    public function create()
    {
        $osdes = OSDE::orderBy('OSDE')->get();
        
        return view('nomencladores.clientes.create', compact('osdes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Cliente' => 'required|string|max:255',
            'Id OSDE' => 'nullable|integer|exists:Nomenclador OSDEs,Id Osde',
            'Bases Presentadas' => 'nullable|boolean',
            'Fecha Bases' => 'nullable|date',
        ]);
        
        Cliente::create($validated);
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function show($id)
    {
        $cliente = Cliente::with('osde')->findOrFail($id);
        return view('nomencladores.clientes.show', compact('cliente'));
    }

    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        $osdes = OSDE::orderBy('OSDE')->get();
        
        return view('nomencladores.clientes.edit', compact('cliente', 'osdes'));
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        
        $validated = $request->validate([
            'Cliente' => 'required|string|max:255',
            'Id OSDE' => 'nullable|integer|exists:Nomenclador OSDEs,Id Osde',
            'Bases Presentadas' => 'nullable|boolean',
            'Fecha Bases' => 'nullable|date',
        ]);
        
        $cliente->update($validated);
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron clientes para eliminar.');
        }
        
        $count = Cliente::whereIn('Id Cliente', $ids)->delete();
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', "Se eliminaron $count clientes correctamente.");
    }

    // Exportaciones
    public function exportExcel(Request $request)
    {
        $query = Cliente::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Cargar relación con OSDE
        $query->with('osde');
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Cliente', $ids);
            }
        }
        
        // Asegurar ordenación válida
        $validSortColumns = ['Id Cliente', 'Cliente', 'Bases Presentadas', 'Fecha Bases', 'Id OSDE'];
        $sortBy = $request->input('sort_by', 'Cliente');
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Cliente';
        }
        
        $sortDir = strtolower($request->input('sort_dir', 'asc'));
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $clientes = $query->get();
        
        return Excel::download(new ClientesExport($clientes), 'clientes.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Cliente::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Cargar relación con OSDE
        $query->with('osde');
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Cliente', $ids);
            }
        }
        
        // Asegurar ordenación válida
        $validSortColumns = ['Id Cliente', 'Cliente', 'Bases Presentadas', 'Fecha Bases', 'Id OSDE'];
        $sortBy = $request->input('sort_by', 'Cliente');
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Cliente';
        }
        
        $sortDir = strtolower($request->input('sort_dir', 'asc'));
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $clientes = $query->get();
        
        $pdf = PDF::loadView('nomencladores.clientes.pdf', [
            'clientes' => $clientes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('clientes.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Cliente::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Cargar relación con OSDE
        $query->with('osde');
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Cliente', $ids);
            }
        }
        
        // Asegurar ordenación válida
        $validSortColumns = ['Id Cliente', 'Cliente', 'Bases Presentadas', 'Fecha Bases', 'Id OSDE'];
        $sortBy = $request->input('sort_by', 'Cliente');
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Cliente';
        }
        
        $sortDir = strtolower($request->input('sort_dir', 'asc'));
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $clientes = $query->get();
        
        return view('nomencladores.clientes.print', [
            'clientes' => $clientes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $query->where('Cliente', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('bases_presentadas')) {
            $query->where('Bases Presentadas', $request->bases_presentadas);
        }
        
        if ($request->filled('osde_id')) {
            $query->where('Id OSDE', $request->osde_id);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->where('Fecha Bases', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->where('Fecha Bases', '<=', $request->fecha_hasta);
        }
        
        // Ordenación para exportaciones con validación
        $sortBy = $request->input('sort_by', 'Cliente');
        $sortDir = strtolower($request->input('sort_dir', 'asc'));
        
        // Validar que sort_dir sea 'asc' o 'desc'
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        // Validar que sort_by no esté vacío y sea una columna válida
        $validSortColumns = ['Id Cliente', 'Cliente', 'Bases Presentadas', 'Fecha Bases', 'Id OSDE'];
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Cliente';
        }
        
        $query->orderBy($sortBy, $sortDir);
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('bases_presentadas')) {
            $filtros[] = "Bases Presentadas: " . ($request->bases_presentadas == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('osde_id')) {
            $osde = OSDE::find($request->osde_id);
            $filtros[] = "OSDE: " . ($osde ? $osde->OSDE : 'Desconocido');
        }
        
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $filtros[] = "Fecha: " . $request->fecha_desde . " a " . $request->fecha_hasta;
        } elseif ($request->filled('fecha_desde')) {
            $filtros[] = "Desde: " . $request->fecha_desde;
        } elseif ($request->filled('fecha_hasta')) {
            $filtros[] = "Hasta: " . $request->fecha_hasta;
        }
        
        return $filtros;
    }
}