<?php

namespace App\Http\Controllers;

use App\Models\MomentoCC;
use Illuminate\Http\Request;
use App\Exports\MomentosCCExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class MomentoCCController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MomentoCC::orderBy('Momento CC', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento CC', 'like', "%{$search}%");
        }
        
        $momentosCC = $query->paginate(20);
        return view('nomencladores.momentos-cc.index', compact('momentosCC'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('nomencladores.momentos-cc.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'Momento CC' => 'required|string|max:50|unique:Nomenclador Momentos CC,Momento CC',
        ]);

        MomentoCC::create([
            'Momento CC' => $request->input('Momento CC'),
        ]);

        return redirect()->route('nomencladores.momento-cc.index')
            ->with('success', 'Momento CC creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $momentoCC = MomentoCC::findOrFail($id);
        return view('nomencladores.momentos-cc.show', compact('momentoCC'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $momentoCC = MomentoCC::findOrFail($id);
        return view('nomencladores.momentos-cc.edit', compact('momentoCC'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $momentoCC = MomentoCC::findOrFail($id);

        $request->validate([
            'Momento CC' => 'required|string|max:50|unique:Nomenclador Momentos CC,Momento CC,' . $id . ',Id MomentoCC',
        ]);

        $momentoCC->update([
            'Momento CC' => $request->input('Momento CC'),
        ]);

        return redirect()->route('nomencladores.momento-cc.index')
            ->with('success', 'Momento CC actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $momentoCC = MomentoCC::findOrFail($id);
        $momentoCC->delete();

        return redirect()->route('nomencladores.momento-cc.index')
            ->with('success', 'Momento CC eliminado correctamente.');
    }
    
    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = MomentoCC::orderBy('Momento CC', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento CC', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id MomentoCC', $ids);
            }
        }
        
        $momentosCC = $query->get();
        
        return Excel::download(new MomentosCCExport($momentosCC), 'momentos-cc.xlsx');
    }
    
    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = MomentoCC::orderBy('Momento CC', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento CC', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id MomentoCC', $ids);
            }
        }
        
        $momentosCC = $query->get();
        
        $pdf = PDF::loadView('nomencladores.momentos-cc.pdf', [
            'momentosCC' => $momentosCC,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('momentos-cc.pdf');
    }
    
    /**
     * Print view
     */
    public function print(Request $request)
    {
        $query = MomentoCC::orderBy('Momento CC', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Momento CC', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id MomentoCC', $ids);
            }
        }
        
        $momentosCC = $query->get();
        
        return view('nomencladores.momentos-cc.print', [
            'momentosCC' => $momentosCC,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Delete multiple records
     */
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador Momentos CC,Id MomentoCC'
        ]);
        
        $ids = $request->selected_ids;
        $count = MomentoCC::whereIn('Id MomentoCC', $ids)->delete();
        
        return redirect()->route('nomencladores.momento-cc.index')
            ->with('success', "Se eliminaron $count momentos CC correctamente.");
    }
    
    /**
     * Get filters text
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