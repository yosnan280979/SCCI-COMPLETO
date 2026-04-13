<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancingSource;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancingSourcesExport;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancingSourceController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancingSource::query();

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Id Fuentefinan', 'like', "%{$search}%")
                  ->orWhere('Fuente Financiamiento', 'like', "%{$search}%");
        }

        if ($request->filled('ct')) {
            $query->where('CT', $request->ct);
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Fuentefinan');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $financingSources = $query->paginate(20)->appends($request->query());

        return view('nomencladores.financing_sources.index', compact('financingSources'));
    }

    public function create()
    {
        return view('nomencladores.financing_sources.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Fuente Financiamiento' => 'required|string|max:255',
            'CT' => 'nullable|boolean',
        ]);

        FinancingSource::create($validated);

        return redirect()->route('nomencladores.financing-sources.index')
                         ->with('success', 'Fuente de financiamiento creada correctamente.');
    }

    public function show($id)
    {
        $financingSource = FinancingSource::findOrFail($id);
        return view('nomencladores.financing_sources.show', compact('financingSource'));
    }

    public function edit($id)
    {
        $financingSource = FinancingSource::findOrFail($id);
        return view('nomencladores.financing_sources.edit', compact('financingSource'));
    }

    public function update(Request $request, $id)
    {
        $financingSource = FinancingSource::findOrFail($id);

        $validated = $request->validate([
            'Fuente Financiamiento' => 'required|string|max:255',
            'CT' => 'nullable|boolean',
        ]);

        $financingSource->update($validated);

        return redirect()->route('nomencladores.financing-sources.index')
                         ->with('success', 'Fuente de financiamiento actualizada correctamente.');
    }

    public function destroy($id)
    {
        $financingSource = FinancingSource::findOrFail($id);
        $financingSource->delete();

        return redirect()->route('nomencladores.financing-sources.index')
                         ->with('success', 'Fuente de financiamiento eliminada correctamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron fuentes de financiamiento para eliminar.');
        }

        $count = FinancingSource::whereIn('Id Fuentefinan', $ids)->delete();

        return redirect()->route('nomencladores.financing-sources.index')
                         ->with('success', "Se eliminaron $count fuentes de financiamiento correctamente.");
    }

    // Exportaciones
    public function exportExcel(Request $request)
    {
        $query = FinancingSource::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Fuentefinan', $ids);
            }
        }
        
        $items = $query->orderBy('Id Fuentefinan')->get();

        if ($items->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No hay registros para exportar.');
        }

        return Excel::download(new FinancingSourcesExport($items), 
            'fuentes_financiamiento.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = FinancingSource::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Fuentefinan', $ids);
            }
        }
        
        $items = $query->orderBy('Id Fuentefinan')->get();

        if ($items->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No hay registros para exportar.');
        }

        $pdf = Pdf::loadView('nomencladores.financing_sources.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        return $pdf->download('fuentes_financiamiento.pdf');
    }

    public function print(Request $request)
    {
        $query = FinancingSource::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Fuentefinan', $ids);
            }
        }
        
        $items = $query->orderBy('Id Fuentefinan')->get();

        return view('nomencladores.financing_sources.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Id Fuentefinan', 'like', "%{$search}%")
                  ->orWhere('Fuente Financiamiento', 'like', "%{$search}%");
        }

        if ($request->filled('ct')) {
            $query->where('CT', $request->ct);
        }
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('ct')) {
            $filtros[] = "CT: " . ($request->ct == '1' ? 'Sí' : 'No');
        }
        
        return $filtros;
    }
}