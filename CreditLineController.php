<?php

namespace App\Http\Controllers;

use App\Models\CreditLine;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CreditLinesExport;
use PDF;

class CreditLineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CreditLine::orderBy('Linea de Crédito', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Linea de Crédito', 'like', "%{$search}%");
        }
        
        $creditLines = $query->paginate(25);
        
        return view('nomencladores.credit_lines.index', compact('creditLines'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('nomencladores.credit_lines.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Linea de Crédito' => 'required|unique:Nomenclador Líneas Crédito,Linea de Crédito',
        ]);
        
        CreditLine::create($validated);
        
        return redirect()->route('nomencladores.credit-lines.index')
            ->with('success', 'Línea de crédito creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // CORREGIDO: variable $creditLine en lugar de $linea
        $creditLine = CreditLine::findOrFail($id);
        return view('nomencladores.credit_lines.show', compact('creditLine'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // CORREGIDO: variable $creditLine en lugar de $linea
        $creditLine = CreditLine::findOrFail($id);
        return view('nomencladores.credit_lines.edit', compact('creditLine'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $creditLine = CreditLine::findOrFail($id);
        
        $validated = $request->validate([
            'Linea de Crédito' => 'required|unique:Nomenclador Líneas Crédito,Linea de Crédito,' . $creditLine->{'Id Lineacredito'} . ',Id Lineacredito',
        ]);
        
        $creditLine->update($validated);
        
        return redirect()->route('nomencladores.credit-lines.index')
            ->with('success', 'Línea de crédito actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $creditLine = CreditLine::findOrFail($id);
        $creditLine->delete();
        
        return redirect()->route('nomencladores.credit-lines.index')
            ->with('success', 'Línea de crédito eliminada exitosamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = CreditLine::orderBy('Linea de Crédito', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Linea de Crédito', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Lineacredito', $ids);
            }
        }
        
        $creditLines = $query->get();
        
        return Excel::download(new CreditLinesExport($creditLines), 'lineas-credito.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = CreditLine::orderBy('Linea de Crédito', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Linea de Crédito', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Lineacredito', $ids);
            }
        }
        
        $creditLines = $query->get();
        
        $pdf = PDF::loadView('nomencladores.credit_lines.pdf', [
            'creditLines' => $creditLines,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('lineas-credito.pdf');
    }
    
    public function print(Request $request)
    {
        $query = CreditLine::orderBy('Linea de Crédito', 'asc');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Linea de Crédito', 'like', "%{$search}%");
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Lineacredito', $ids);
            }
        }
        
        $creditLines = $query->get();
        
        return view('nomencladores.credit_lines.print', [
            'creditLines' => $creditLines,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Nomenclador Líneas Crédito,Id Lineacredito'
        ]);
        
        $ids = $request->ids;
        $count = CreditLine::whereIn('Id Lineacredito', $ids)->delete();
        
        return redirect()->route('nomencladores.credit-lines.index')
            ->with('success', "Se eliminaron $count líneas de crédito correctamente.");
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