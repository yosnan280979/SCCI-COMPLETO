<?php

namespace App\Http\Controllers;

use App\Models\MomentoSOE;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MomentoSOEExport;
use PDF;

class MomentoSOEController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MomentoSOE::orderBy('Momento SOE', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento SOE', 'like', "%{$search}%");
        }
        
        $momentosSOE = $query->paginate(20);
        return view('nomencladores.momentos-soe.index', compact('momentosSOE'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('nomencladores.momentos-soe.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'Momento SOE' => 'required|string|max:50|unique:Nomenclador de Momentos SOE,Momento SOE',
        ]);

        MomentoSOE::create([
            'Momento SOE' => $request->input('Momento SOE'),
        ]);

        return redirect()->route('nomencladores.momento-soe.index')
            ->with('success', 'Momento SOE creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $momentoSOE = MomentoSOE::findOrFail($id);
        return view('nomencladores.momentos-soe.show', compact('momentoSOE'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $momentoSOE = MomentoSOE::findOrFail($id);
        return view('nomencladores.momentos-soe.edit', compact('momentoSOE'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $momentoSOE = MomentoSOE::findOrFail($id);

        $request->validate([
            'Momento SOE' => 'required|string|max:50|unique:Nomenclador de Momentos SOE,Momento SOE,' . $id . ',Id MomentoSOE',
        ]);

        $momentoSOE->update([
            'Momento SOE' => $request->input('Momento SOE'),
        ]);

        return redirect()->route('nomencladores.momento-soe.index')
            ->with('success', 'Momento SOE actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $momentoSOE = MomentoSOE::findOrFail($id);
        $momentoSOE->delete();

        return redirect()->route('nomencladores.momento-soe.index')
            ->with('success', 'Momento SOE eliminado correctamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = MomentoSOE::orderBy('Momento SOE', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento SOE', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id MomentoSOE', $ids);
            }
        }
        
        $momentosSOE = $query->get();
        
        return Excel::download(new MomentoSOEExport($momentosSOE), 'momentos-soe.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = MomentoSOE::orderBy('Momento SOE', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento SOE', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id MomentoSOE', $ids);
            }
        }
        
        $momentosSOE = $query->get();
        
        $pdf = PDF::loadView('nomencladores.momentos-soe.pdf', [
            'momentosSOE' => $momentosSOE,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('momentos-soe.pdf');
    }
    
    public function print(Request $request)
    {
        $query = MomentoSOE::orderBy('Momento SOE', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento SOE', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id MomentoSOE', $ids);
            }
        }
        
        $momentosSOE = $query->get();
        
        return view('nomencladores.momentos-soe.print', [
            'momentosSOE' => $momentosSOE,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador de Momentos SOE,Id MomentoSOE',
        ]);
        
        $count = MomentoSOE::whereIn('Id MomentoSOE', $request->selected_ids)->delete();
        
        return redirect()->route('nomencladores.momento-soe.index')
            ->with('success', "Se eliminaron $count Momentos SOE correctamente.");
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        return $filtros;
    }
}