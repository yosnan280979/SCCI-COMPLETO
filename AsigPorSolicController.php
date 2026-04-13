<?php

namespace App\Http\Controllers;

use App\Models\AsigPorSolic;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsigPorSolicExport;
use PDF;

class AsigPorSolicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AsigPorSolic::query();
        
        // Obtener solicitudes para el select de filtro
        $solicitudes = Solicitud::orderBy('Id Solicitud', 'desc')->get();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Solicitud', 'like', "%{$search}%")
                  ->orWhere('Año finan', 'like', "%{$search}%")
                  ->orWhere('Asig MCUC', 'like', "%{$search}%");
            });
        }
        
        // Filtro por ID de Solicitud específico
        if ($request->filled('id_solicitud')) {
            $query->where('Id Solicitud', $request->id_solicitud);
        }
        
        // Filtro por rango de años
        if ($request->filled('ano_desde')) {
            $query->where('Año finan', '>=', $request->ano_desde);
        }
        
        if ($request->filled('ano_hasta')) {
            $query->where('Año finan', '<=', $request->ano_hasta);
        }
        
        // Filtro por rango de Asig MCUC
        if ($request->filled('mcuc_desde')) {
            $query->where('Asig MCUC', '>=', $request->mcuc_desde);
        }
        
        if ($request->filled('mcuc_hasta')) {
            $query->where('Asig MCUC', '<=', $request->mcuc_hasta);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Solicitud');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $asigporsolic = $query->paginate(20)->appends($request->query());
        
        return view('asigporsolic.index', compact('asigporsolic', 'solicitudes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $solicitudes = Solicitud::orderBy('Id Solicitud', 'desc')->get();
        return view('asigporsolic.create', compact('solicitudes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Solicitud' => 'required|integer|unique:Asig por solic,Id Solicitud',
            'Año finan' => 'required|integer|min:2000|max:2100',
            'Asig MCUC' => 'nullable|numeric|min:0',
        ]);
        
        AsigPorSolic::create($validated);
        
        return redirect()->route('asigporsolic.index')
            ->with('success', 'Asignación creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $asigporsolic = AsigPorSolic::findOrFail($id);
        return view('asigporsolic.show', compact('asigporsolic'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $asigporsolic = AsigPorSolic::findOrFail($id);
        $solicitudes = Solicitud::orderBy('Id Solicitud', 'desc')->get();
        return view('asigporsolic.edit', compact('asigporsolic', 'solicitudes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $asigporsolic = AsigPorSolic::findOrFail($id);
        
        $validated = $request->validate([
            'Id Solicitud' => 'required|integer|unique:Asig por solic,Id Solicitud,' . $asigporsolic->{'Id Solicitud'} . ',Id Solicitud',
            'Año finan' => 'required|integer|min:2000|max:2100',
            'Asig MCUC' => 'nullable|numeric|min:0',
        ]);
        
        $asigporsolic->update($validated);
        
        return redirect()->route('asigporsolic.index')
            ->with('success', 'Asignación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $asigporsolic = AsigPorSolic::findOrFail($id);
        $asigporsolic->delete();
        
        return redirect()->route('asigporsolic.index')
            ->with('success', 'Asignación eliminada exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron asignaciones para eliminar.');
        }
        
        $count = AsigPorSolic::whereIn('Id Solicitud', $ids)->delete();
        
        return redirect()->route('asigporsolic.index')
            ->with('success', "Se eliminaron $count asignaciones correctamente.");
    }

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = AsigPorSolic::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Solicitud', $ids);
            }
        }
        
        $asigporsolic = $query->orderBy('Id Solicitud', 'desc')->get();
        
        return Excel::download(new AsigPorSolicExport($asigporsolic), 'asigporsolic.xlsx');
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = AsigPorSolic::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Solicitud', $ids);
            }
        }
        
        $asigporsolic = $query->orderBy('Id Solicitud', 'desc')->get();
        
        $pdf = PDF::loadView('asigporsolic.pdf', [
            'asigporsolic' => $asigporsolic,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('asigporsolic.pdf');
    }

    /**
     * Imprimir listado
     */
    public function print(Request $request)
    {
        $query = AsigPorSolic::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Solicitud', $ids);
            }
        }
        
        $asigporsolic = $query->orderBy('Id Solicitud', 'desc')->get();
        
        return view('asigporsolic.print', [
            'asigporsolic' => $asigporsolic,
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
                $q->where('Id Solicitud', 'like', "%{$search}%")
                  ->orWhere('Año finan', 'like', "%{$search}%")
                  ->orWhere('Asig MCUC', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_solicitud')) {
            $query->where('Id Solicitud', $request->id_solicitud);
        }
        
        if ($request->filled('ano_desde')) {
            $query->where('Año finan', '>=', $request->ano_desde);
        }
        
        if ($request->filled('ano_hasta')) {
            $query->where('Año finan', '<=', $request->ano_hasta);
        }
        
        if ($request->filled('mcuc_desde')) {
            $query->where('Asig MCUC', '>=', $request->mcuc_desde);
        }
        
        if ($request->filled('mcuc_hasta')) {
            $query->where('Asig MCUC', '<=', $request->mcuc_hasta);
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDirection = $request->get('sort_dir', 'desc');
            $query->orderBy($request->sort_by, $sortDirection);
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
        
        if ($request->filled('id_solicitud')) {
            $filtros[] = "ID Solicitud: " . $request->id_solicitud;
        }
        
        if ($request->filled('ano_desde') || $request->filled('ano_hasta')) {
            $rango = '';
            if ($request->filled('ano_desde')) {
                $rango .= "Desde: " . $request->ano_desde . " ";
            }
            if ($request->filled('ano_hasta')) {
                $rango .= "Hasta: " . $request->ano_hasta;
            }
            $filtros[] = "Año financiero: " . trim($rango);
        }
        
        if ($request->filled('mcuc_desde') || $request->filled('mcuc_hasta')) {
            $rango = '';
            if ($request->filled('mcuc_desde')) {
                $rango .= "Desde: " . $request->mcuc_desde . " ";
            }
            if ($request->filled('mcuc_hasta')) {
                $rango .= "Hasta: " . $request->mcuc_hasta;
            }
            $filtros[] = "Asignación MCUC: " . trim($rango);
        }
        
        if ($request->filled('sort_by')) {
            $sortDirection = $request->get('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . ($sortDirection == 'asc' ? 'Ascendente' : 'Descendente') . ")";
        }
        
        return $filtros;
    }
}