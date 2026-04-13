<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClassificationsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ClassificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Classification::query();

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Clasificacion', 'like', "%{$search}%")
                  ->orWhere('Clasificacion', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('con_valor_ctto')) {
            $query->where('Con Valor Ctto', $request->con_valor_ctto);
        }
        
        if ($request->filled('orden')) {
            $query->where('Orden', $request->orden);
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Clasificacion');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $classifications = $query->paginate(20)->appends($request->query());

        return view('nomencladores.classifications.index', compact('classifications'));
    }

    public function create()
    {
        return view('nomencladores.classifications.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Clasificacion' => 'required|string|max:255',
            'Con Valor Ctto' => 'nullable|boolean',
            'Orden' => 'nullable|numeric',
        ]);

        Classification::create($validated);

        return redirect()->route('nomencladores.classifications.index')
                         ->with('success', 'Clasificación creada correctamente.');
    }

    public function show($id)
    {
        $classification = Classification::findOrFail($id);
        return view('nomencladores.classifications.show', compact('classification'));
    }

    public function edit($id)
    {
        $classification = Classification::findOrFail($id);
        return view('nomencladores.classifications.edit', compact('classification'));
    }

    public function update(Request $request, $id)
    {
        $classification = Classification::findOrFail($id);

        $validated = $request->validate([
            'Clasificacion' => 'required|string|max:255',
            'Con Valor Ctto' => 'nullable|boolean',
            'Orden' => 'nullable|numeric',
        ]);

        $classification->update($validated);

        return redirect()->route('nomencladores.classifications.index')
                         ->with('success', 'Clasificación actualizada correctamente.');
    }

    public function destroy($id)
    {
        $classification = Classification::findOrFail($id);
        $classification->delete();

        return redirect()->route('nomencladores.classifications.index')
                         ->with('success', 'Clasificación eliminada correctamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron clasificaciones para eliminar.');
        }
        
        $count = Classification::whereIn('Id Clasificacion', $ids)->delete();
        
        return redirect()->route('nomencladores.classifications.index')
                         ->with('success', "Se eliminaron $count clasificaciones correctamente.");
    }

    // Exportaciones
    public function exportExcel(Request $request)
    {
        $query = Classification::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Clasificacion', $ids);
            }
        }
        
        $items = $query->orderBy('Id Clasificacion')->get();

        if ($items->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No hay registros para exportar.');
        }

        return Excel::download(new ClassificationsExport($items), 
            'clasificaciones_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = Classification::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Clasificacion', $ids);
            }
        }
        
        $items = $query->orderBy('Id Clasificacion')->get();

        if ($items->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No hay registros para exportar.');
        }

        $pdf = Pdf::loadView('nomencladores.classifications.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        return $pdf->download('clasificaciones_' . date('Y-m-d') . '.pdf');
    }

    public function print(Request $request)
    {
        $query = Classification::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Clasificacion', $ids);
            }
        }
        
        $items = $query->orderBy('Id Clasificacion')->get();

        return view('nomencladores.classifications.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Clasificacion', 'like', "%{$search}%")
                  ->orWhere('Clasificacion', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('con_valor_ctto')) {
            $query->where('Con Valor Ctto', $request->con_valor_ctto);
        }
        
        if ($request->filled('orden')) {
            $query->where('Orden', $request->orden);
        }
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
        
        if ($request->filled('con_valor_ctto')) {
            $filtros[] = "Con Valor Contrato: " . ($request->con_valor_ctto == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('orden')) {
            $filtros[] = "Orden: " . $request->orden;
        }
        
        return $filtros;
    }
}