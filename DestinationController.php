<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DestinationsExport;
use PDF;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $query = Destination::query();
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('destino', 'like', "%{$search}%");
        }
        
        // Ordenación con validación
        $sortBy = $this->validarSortBy($request->input('sort_by', 'Id Destino'));
        $sortDir = $this->validarSortDir($request->input('sort_dir', 'desc'));
        
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $destinations = $query->paginate(20)->appends($request->query());
        
        return view('nomencladores.destinations.index', compact('destinations'));
    }

    public function create()
    {
        return view('nomencladores.destinations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destino' => 'required|string|max:100|unique:Nomenclador Destinos,destino'
        ]);
        
        Destination::create($validated);
        
        return redirect()->route('nomencladores.destinations.index')
            ->with('success', 'Destino creado exitosamente.');
    }

    public function show($id)
    {
        $destination = Destination::findOrFail($id);
        return view('nomencladores.destinations.show', compact('destination'));
    }

    public function edit($id)
    {
        $destination = Destination::findOrFail($id);
        return view('nomencladores.destinations.edit', compact('destination'));
    }

    public function update(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);
        
        $validated = $request->validate([
            'destino' => 'required|string|max:100|unique:Nomenclador Destinos,destino,' . $id . ',Id Destino'
        ]);
        
        $destination->update($validated);
        
        return redirect()->route('nomencladores.destinations.index')
            ->with('success', 'Destino actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $destination = Destination::findOrFail($id);
        $destination->delete();
        
        return redirect()->route('nomencladores.destinations.index')
            ->with('success', 'Destino eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron destinos para eliminar.');
        }
        
        $count = Destination::whereIn('Id Destino', $ids)->delete();
        
        return redirect()->route('nomencladores.destinations.index')
            ->with('success', "Se eliminaron $count destinos correctamente.");
    }

    // Exportaciones
    public function exportExcel(Request $request)
    {
        $query = Destination::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('destino', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Destino', $ids);
            }
        }
        
        // Ordenación con validación segura
        $sortBy = $this->validarSortBy($request->input('sort_by', 'Id Destino'));
        $sortDir = $this->validarSortDir($request->input('sort_dir', 'desc'));
        
        $query->orderBy($sortBy, $sortDir);
        
        $destinations = $query->get();
        
        return Excel::download(new DestinationsExport($destinations), 'destinos.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Destination::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('destino', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Destino', $ids);
            }
        }
        
        // Ordenación con validación segura
        $sortBy = $this->validarSortBy($request->input('sort_by', 'Id Destino'));
        $sortDir = $this->validarSortDir($request->input('sort_dir', 'desc'));
        
        $query->orderBy($sortBy, $sortDir);
        
        $destinations = $query->get();
        
        $pdf = PDF::loadView('nomencladores.destinations.pdf', [
            'destinations' => $destinations,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape'); // Orientación horizontal
        
        return $pdf->download('destinos.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Destination::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('destino', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Destino', $ids);
            }
        }
        
        // Ordenación con validación segura
        $sortBy = $this->validarSortBy($request->input('sort_by', 'Id Destino'));
        $sortDir = $this->validarSortDir($request->input('sort_dir', 'desc'));
        
        $query->orderBy($sortBy, $sortDir);
        
        $destinations = $query->get();
        
        return view('nomencladores.destinations.print', [
            'destinations' => $destinations,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Validar y asegurar que sort_by sea una columna válida
     */
    private function validarSortBy($sortBy)
    {
        // Si está vacío, usar valor por defecto
        if (empty($sortBy) || trim($sortBy) === '') {
            return 'Id Destino';
        }
        
        // Lista de columnas válidas en la tabla
        $columnasValidas = ['Id Destino', 'destino'];
        
        // Si la columna no es válida, usar valor por defecto
        if (!in_array($sortBy, $columnasValidas)) {
            return 'Id Destino';
        }
        
        return $sortBy;
    }
    
    /**
     * Validar dirección de ordenación
     */
    private function validarSortDir($sortDir)
    {
        $sortDir = strtolower($sortDir);
        
        if (!in_array($sortDir, ['asc', 'desc'])) {
            return 'desc';
        }
        
        return $sortDir;
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $this->validarSortDir($request->input('sort_dir', 'desc'));
            $sortBy = $this->validarSortBy($request->input('sort_by'));
            $filtros[] = "Ordenado por: " . $sortBy . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}