<?php

namespace App\Http\Controllers;

use App\Models\Ministerio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MinisteriosExport;
use PDF;

class MinisterioController extends Controller
{
    public function index(Request $request)
    {
        $query = Ministerio::query();
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Ministerio', 'like', "%{$search}%");
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Ministerio');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $ministerios = $query->paginate(20)->appends($request->query());
        
        return view('nomencladores.ministries.index', compact('ministerios'));
    }

    public function create()
    {
        return view('nomencladores.ministries.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Ministerio' => 'required|string|max:60|unique:Nomenclador de Ministerios,Ministerio',
        ]);

        Ministerio::create($validated);
        
        return redirect()->route('nomencladores.ministries.index')
            ->with('success', 'Ministerio creado exitosamente.');
    }

    public function show($id)
    {
        $ministerio = Ministerio::findOrFail($id);
        return view('nomencladores.ministries.show', compact('ministerio'));
    }

    public function edit($id)
    {
        $ministerio = Ministerio::findOrFail($id);
        return view('nomencladores.ministries.edit', compact('ministerio'));
    }

    public function update(Request $request, $id)
    {
        $ministerio = Ministerio::findOrFail($id);
        
        $validated = $request->validate([
            'Ministerio' => 'required|string|max:60|unique:Nomenclador de Ministerios,Ministerio,' . $id . ',Id Ministerio',
        ]);

        $ministerio->update($validated);
        
        return redirect()->route('nomencladores.ministries.index')
            ->with('success', 'Ministerio actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $ministerio = Ministerio::findOrFail($id);
        $ministerio->delete();
        
        return redirect()->route('nomencladores.ministries.index')
            ->with('success', 'Ministerio eliminado exitosamente.');
    }
    
    // Eliminar múltiples ministerios
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron ministerios para eliminar.');
        }
        
        $count = Ministerio::whereIn('Id Ministerio', $ids)->delete();
        
        return redirect()->route('nomencladores.ministries.index')
            ->with('success', "Se eliminaron {$count} ministerios correctamente.");
    }
    
    public function exportExcel(Request $request)
    {
        $query = Ministerio::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Ministerio', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ministerio', $ids);
            }
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Ministerio');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);
        
        $ministerios = $query->get();
        
        return Excel::download(new MinisteriosExport($ministerios), 'ministerios_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Ministerio::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Ministerio', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ministerio', $ids);
            }
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Ministerio');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);
        
        $ministerios = $query->get();
        
        $pdf = PDF::loadView('nomencladores.ministries.pdf', ['ministerios' => $ministerios]);
        return $pdf->download('ministerios_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Ministerio::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Ministerio', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ministerio', $ids);
            }
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Ministerio');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);
        
        $ministerios = $query->get();
        
        return view('nomencladores.ministries.print', compact('ministerios'));
    }
}