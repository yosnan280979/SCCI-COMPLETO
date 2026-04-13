<?php

namespace App\Http\Controllers;

use App\Models\DescripcionSolicitud;
use App\Models\Solicitud;
use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DescripcionSolicitudExport;
use PDF;

class DescripcionSolicitudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DescripcionSolicitud::with(['solicitud', 'moneda']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Producto', 'like', "%{$search}%")
                  ->orWhere('UM', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_solicitud')) {
            $query->where('Id Solicitud', $request->input('id_solicitud'));
        }
        
        if ($request->filled('precio_cuc_desde')) {
            $query->where('Precio CUC', '>=', $request->input('precio_cuc_desde'));
        }
        
        if ($request->filled('precio_cuc_hasta')) {
            $query->where('Precio CUC', '<=', $request->input('precio_cuc_hasta'));
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $descripciones = $query->paginate(20)->appends($request->query());
        
        // Cambio importante: usar otro nombre para la lista de solicitudes
        $listaSolicitudes = Solicitud::orderBy('No Solicitud')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('descripcion-solicitud.index', compact('descripciones', 'listaSolicitudes', 'monedas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('descripcion-solicitud.create', compact('solicitudes', 'monedas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Solicitud' => 'required|exists:Solicitudes,Id Solicitud',
            'Producto' => 'required|string|max:250',
            'UM' => 'required|string|max:12',
            'Cantidad' => 'required|numeric',
            'Precio CUC' => 'required|numeric',
            'Precio Mon Prov' => 'nullable|numeric',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
        ]);
        
        DescripcionSolicitud::create($validated);
        
        return redirect()->route('descripcion-solicitud.index')
            ->with('success', 'Descripción creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $descripcion = DescripcionSolicitud::findOrFail($id);
        return view('descripcion-solicitud.show', compact('descripcion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $descripcion = DescripcionSolicitud::findOrFail($id);
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('descripcion-solicitud.edit', compact('descripcion', 'solicitudes', 'monedas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $descripcion = DescripcionSolicitud::findOrFail($id);
        
        $validated = $request->validate([
            'Id Solicitud' => 'required|exists:Solicitudes,Id Solicitud',
            'Producto' => 'required|string|max:250',
            'UM' => 'required|string|max:12',
            'Cantidad' => 'required|numeric',
            'Precio CUC' => 'required|numeric',
            'Precio Mon Prov' => 'nullable|numeric',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
        ]);
        
        $descripcion->update($validated);
        
        return redirect()->route('descripcion-solicitud.index')
            ->with('success', 'Descripción actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $descripcion = DescripcionSolicitud::findOrFail($id);
        $descripcion->delete();
        
        return redirect()->route('descripcion-solicitud.index')
            ->with('success', 'Descripción eliminada exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron descripciones para eliminar.');
        }
        
        $count = DescripcionSolicitud::whereIn('Id', $ids)->delete();
        
        return redirect()->route('descripcion-solicitud.index')
            ->with('success', "Se eliminaron $count descripciones correctamente.");
    }

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = DescripcionSolicitud::with(['solicitud', 'moneda']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $descripciones = $query->orderBy('Id')->get();
        
        // Asegurarse de que el exportador existe
        if (!class_exists('App\\Exports\\DescripcionSolicitudExport')) {
            return back()->with('error', 'El exportador de Excel no está configurado.');
        }
        
        return Excel::download(new DescripcionSolicitudExport($descripciones), 'descripcion-solicitud.xlsx');
    }
    
    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = DescripcionSolicitud::with(['solicitud', 'moneda']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $descripciones = $query->orderBy('Id')->get();
        
        // Verificar si hay datos para exportar
        if ($descripciones->isEmpty()) {
            return back()->with('warning', 'No hay datos para exportar.');
        }
        
        $pdf = PDF::loadView('descripcion-solicitud.pdf', [
            'descripciones' => $descripciones,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('descripcion-solicitud.pdf');
    }
    
    /**
     * Imprimir listado
     */
    public function print(Request $request)
    {
        $query = DescripcionSolicitud::with(['solicitud', 'moneda']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $descripciones = $query->orderBy('Id')->get();
        
        return view('descripcion-solicitud.print', [
            'descripciones' => $descripciones,
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
                $q->where('Producto', 'like', "%{$search}%")
                  ->orWhere('UM', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_solicitud')) {
            $query->where('Id Solicitud', $request->input('id_solicitud'));
        }
        
        if ($request->filled('precio_cuc_desde')) {
            $query->where('Precio CUC', '>=', $request->input('precio_cuc_desde'));
        }
        
        if ($request->filled('precio_cuc_hasta')) {
            $query->where('Precio CUC', '<=', $request->input('precio_cuc_hasta'));
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
            $solicitud = Solicitud::find($request->input('id_solicitud'));
            $filtros[] = "Solicitud: " . ($solicitud ? $solicitud->{'No Solicitud'} : 'Desconocido');
        }
        
        if ($request->filled('precio_cuc_desde')) {
            $filtros[] = "Precio CUC desde: " . number_format($request->precio_cuc_desde, 2);
        }
        
        if ($request->filled('precio_cuc_hasta')) {
            $filtros[] = "Precio CUC hasta: " . number_format($request->precio_cuc_hasta, 2);
        }
        
        return $filtros;
    }
}