<?php

namespace App\Http\Controllers;

use App\Models\SolicitudesProveedores;
use App\Models\Solicitud;
use App\Models\Provider;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SolicitudesProveedoresExport;
use PDF;

class SolicitudProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SolicitudesProveedores::query();
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Solicitud', 'like', "%{$search}%")
                  ->orWhere('Id Proveedor', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%")
                  ->orWhere('Id Ctto', 'like', "%{$search}%")
                  ->orWhere('Id Tipo Producto', 'like', "%{$search}%")
                  ->orWhere('Id Tipo Respuesta', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_solicitud')) {
            $query->where('Id Solicitud', $request->id_solicitud);
        }
        
        if ($request->filled('id_proveedor')) {
            $query->where('Id Proveedor', $request->id_proveedor);
        }
        
        if ($request->filled('respuesta')) {
            $query->where('Respuesta', $request->respuesta);
        }
        
        if ($request->filled('seleccionado')) {
            $query->where('Selecionado', $request->seleccionado);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Oferta', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Oferta', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('id_ctto')) {
            $query->where('Id Ctto', 'like', "%{$request->id_ctto}%");
        }
        
        if ($request->filled('id_tipo_producto')) {
            $query->where('Id Tipo Producto', 'like', "%{$request->id_tipo_producto}%");
        }
        
        if ($request->filled('id_tipo_respuesta')) {
            $query->where('Id Tipo Respuesta', 'like', "%{$request->id_tipo_respuesta}%");
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $solicitudes_proveedores = $query->paginate(20)->appends($request->query());
        
        // Obtener datos para filtros
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $providers = Provider::query()
            ->orderBy('Proveedor')
            ->get();
        
        return view('solicitudes-proveedores.index', compact('solicitudes_proveedores', 'solicitudes', 'providers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $providers = Provider::query()
            ->orderBy('Proveedor')
            ->get();
        
        return view('solicitudes-proveedores.create', compact('solicitudes', 'providers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Solicitud' => 'required|exists:Solicitudes,Id Solicitud',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Respuesta' => 'nullable|boolean',
            'Fecha Oferta' => 'nullable|date',
            'Selecionado' => 'nullable|boolean',
            'Id Ctto' => 'nullable|exists:Contratos,Id Ctto',
            'Observaciones' => 'nullable|string',
            'Id Tipo Producto' => 'nullable|exists:Nomenclador Tipos Productos,Id Tipo Producto',
            'Id Tipo Respuesta' => 'nullable|exists:Nomenclador de Tipos de respuestas,Id Tipo respuesta',
            'Fecha Dictec' => 'nullable|date',
        ]);
        
        SolicitudesProveedores::create($validated);
        
        return redirect()->route('solicitudes-proveedores.index')
            ->with('success', 'Solicitud de proveedor creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $solicitud = SolicitudesProveedores::findOrFail($id);
        return view('solicitudes-proveedores.show', compact('solicitud'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $solicitud = SolicitudesProveedores::findOrFail($id);
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $providers = Provider::query()
            ->orderBy('Proveedor')
            ->get();
        
        return view('solicitudes-proveedores.edit', compact('solicitud', 'solicitudes', 'providers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $solicitud = SolicitudesProveedores::findOrFail($id);
        
        $validated = $request->validate([
            'Id Solicitud' => 'required|exists:Solicitudes,Id Solicitud',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Respuesta' => 'nullable|boolean',
            'Fecha Oferta' => 'nullable|date',
            'Selecionado' => 'nullable|boolean',
            'Id Ctto' => 'nullable|exists:Contratos,Id Ctto',
            'Observaciones' => 'nullable|string',
            'Id Tipo Producto' => 'nullable|exists:Nomenclador Tipos Productos,Id Tipo Producto',
            'Id Tipo Respuesta' => 'nullable|exists:Nomenclador de Tipos de respuestas,Id Tipo respuesta',
            'Fecha Dictec' => 'nullable|date',
        ]);
        
        $solicitud->update($validated);
        
        return redirect()->route('solicitudes-proveedores.index')
            ->with('success', 'Solicitud de proveedor actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $solicitud = SolicitudesProveedores::findOrFail($id);
        $solicitud->delete();
        
        return redirect()->route('solicitudes-proveedores.index')
            ->with('success', 'Solicitud de proveedor eliminada exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron solicitudes de proveedores para eliminar.');
        }
        
        $count = SolicitudesProveedores::whereIn('Id', $ids)->delete();
        
        return redirect()->route('solicitudes-proveedores.index')
            ->with('success', "Se eliminaron $count solicitudes de proveedores correctamente.");
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = SolicitudesProveedores::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $solicitudes = $query->orderBy('Id')->get();
        
        return Excel::download(new SolicitudesProveedoresExport($solicitudes), 'solicitudes-proveedores.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = SolicitudesProveedores::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $solicitudes = $query->orderBy('Id')->get();
        
        $pdf = PDF::loadView('solicitudes-proveedores.pdf', [
            'solicitudes' => $solicitudes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('solicitudes-proveedores.pdf');
    }
    
    public function print(Request $request)
    {
        $query = SolicitudesProveedores::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $solicitudes = $query->orderBy('Id')->get();
        
        return view('solicitudes-proveedores.print', [
            'solicitudes' => $solicitudes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Solicitud', 'like', "%{$search}%")
                  ->orWhere('Id Proveedor', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%")
                  ->orWhere('Id Ctto', 'like', "%{$search}%")
                  ->orWhere('Id Tipo Producto', 'like', "%{$search}%")
                  ->orWhere('Id Tipo Respuesta', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_solicitud')) {
            $query->where('Id Solicitud', $request->id_solicitud);
        }
        
        if ($request->filled('id_proveedor')) {
            $query->where('Id Proveedor', $request->id_proveedor);
        }
        
        if ($request->filled('respuesta')) {
            $query->where('Respuesta', $request->respuesta);
        }
        
        if ($request->filled('seleccionado')) {
            $query->where('Selecionado', $request->seleccionado);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Oferta', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Oferta', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('id_ctto')) {
            $query->where('Id Ctto', 'like', "%{$request->id_ctto}%");
        }
        
        if ($request->filled('id_tipo_producto')) {
            $query->where('Id Tipo Producto', 'like', "%{$request->id_tipo_producto}%");
        }
        
        if ($request->filled('id_tipo_respuesta')) {
            $query->where('Id Tipo Respuesta', 'like', "%{$request->id_tipo_respuesta}%");
        }
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('id_solicitud')) {
            $solicitud = Solicitud::find($request->id_solicitud);
            $filtros[] = "Solicitud: " . ($solicitud ? "ID: {$solicitud->{'Id Solicitud'}} - No: {$solicitud->{'No Solicitud'}}" : $request->id_solicitud);
        }
        
        if ($request->filled('id_proveedor')) {
            $proveedor = Provider::find($request->id_proveedor);
            $nombre = $proveedor ? $proveedor->Proveedor : '';
            $filtros[] = "Proveedor: " . ($nombre ? "{$nombre} (ID: {$request->id_proveedor})" : "ID: {$request->id_proveedor}");
        }
        
        if ($request->filled('respuesta')) {
            $filtros[] = "Respuesta: " . ($request->respuesta == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('seleccionado')) {
            $filtros[] = "Seleccionado: " . ($request->seleccionado == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $filtros[] = "Fecha Oferta: " . $request->fecha_desde . " a " . $request->fecha_hasta;
        } elseif ($request->filled('fecha_desde')) {
            $filtros[] = "Fecha Oferta desde: " . $request->fecha_desde;
        } elseif ($request->filled('fecha_hasta')) {
            $filtros[] = "Fecha Oferta hasta: " . $request->fecha_hasta;
        }
        
        if ($request->filled('id_ctto')) {
            $filtros[] = "ID Contrato: " . $request->id_ctto;
        }
        
        if ($request->filled('id_tipo_producto')) {
            $filtros[] = "ID Tipo Producto: " . $request->id_tipo_producto;
        }
        
        if ($request->filled('id_tipo_respuesta')) {
            $filtros[] = "ID Tipo Respuesta: " . $request->id_tipo_respuesta;
        }
        
        return $filtros;
    }
}