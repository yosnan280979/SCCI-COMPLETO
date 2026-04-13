<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Cliente;
use App\Models\Specialist;
use App\Models\BalanceCenter;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SolicitudesExport;
use PDF;

class SolicitudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Solicitud::with(['cliente', 'especialista', 'balanceCenter']);
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('No Solicitud', 'like', "%{$search}%")
                  ->orWhere('Decripción Solicitud', 'like', "%{$search}%")
                  ->orWhere('Observ Esp', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('Id Cliente')) {
            $query->where('Id Cliente', $request->input('Id Cliente'));
        }
        
        if ($request->filled('Id Especialista')) {
            $query->where('Id Especialista', $request->input('Id Especialista'));
        }
        
        if ($request->filled('Id Centro Balance')) {
            $query->where('Id Centro Balance', $request->input('Id Centro Balance'));
        }
        
        if ($request->filled('Id OSDE')) {
            $query->where('Id OSDE', $request->input('Id OSDE'));
        }
        
        if ($request->filled('Id Destino')) {
            $query->where('Id Destino', $request->input('Id Destino'));
        }
        
        if ($request->filled('Id Clasificacion')) {
            $query->where('Id Clasificacion', $request->input('Id Clasificacion'));
        }
        
        if ($request->filled('Id Tipo Operacion')) {
            $query->where('Id Tipo Operacion', $request->input('Id Tipo Operacion'));
        }
        
        if ($request->filled('Devuelta')) {
            $query->where('Devuelta', $request->input('Devuelta'));
        }
        
        if ($request->filled('Cancelada')) {
            $query->where('Cancelada', $request->input('Cancelada'));
        }
        
        if ($request->filled('Pendiente Contratar')) {
            $query->where('Pendiente Contratar', $request->input('Pendiente Contratar'));
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Solicitud', '>=', $request->input('fecha_desde'));
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Solicitud', '<=', $request->input('fecha_hasta'));
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Fecha Solicitud');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $solicitudes = $query->paginate(20)->appends($request->query());
        
        $clientes = Cliente::orderBy('Cliente')->get();
        $especialistas = Specialist::where('Activos', 1)->orderBy('Especialista')->get();
        $centrosBalance = BalanceCenter::where('Activos', 1)->orderBy('Centro Balance')->get();
        
        return view('solicitudes.index', compact('solicitudes', 'clientes', 'especialistas', 'centrosBalance'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Cliente::orderBy('Cliente')->get();
        $especialistas = Specialist::where('Activos', 1)->orderBy('Especialista')->get();
        $centrosBalance = BalanceCenter::where('Activos', 1)->orderBy('Centro Balance')->get();
        
        return view('solicitudes.create', compact('clientes', 'especialistas', 'centrosBalance'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'No Solicitud' => 'required|unique:Solicitudes,No Solicitud',
            'Fecha Solicitud' => 'required|date',
            'Id Cliente' => 'required|exists:Nomenclador Clientes,Id Cliente',
            'Id Especialista' => 'required|exists:Nomenclador Especialistas,Id especialista',
            'Id Centro Balance' => 'required|exists:Nomenclador Centros Balance,Id Centro Balnce',
            'Observaciones' => 'nullable|string',
            'Decripción Solicitud' => 'nullable|string',
            'Fecha rec sol' => 'nullable|date',
            'Fecha acep aduana' => 'nullable|date',
            'Fecha acep esp' => 'nullable|date',
            'Asignado MCUC' => 'nullable|boolean',
            'Distribuido MCUC' => 'nullable|boolean',
            'Fecha Salida Mercado' => 'nullable|date',
            'Fecha primera oferta' => 'nullable|date',
            'Id OSDE' => 'nullable|integer',
            'Id Destino' => 'nullable|integer',
            'Id Clasificacion' => 'nullable|integer',
            'Id Tipo Operacion' => 'nullable|integer',
            'Id Linea Credito' => 'nullable|integer',
            'Id Fuentefinan' => 'nullable|integer',
            'Id Tipo prodg' => 'nullable|integer',
            'Dias Oferta' => 'nullable|integer',
            'añosol' => 'nullable|integer',
            'Observ Esp' => 'nullable|string',
            'Devuelta' => 'nullable|boolean',
            'Fecha Dev' => 'nullable|date',
            'Cancelada' => 'nullable|boolean',
            'Fecha Can' => 'nullable|date',
            'añofinan' => 'nullable|integer',
            'Pendiente Contratar' => 'nullable|boolean',
            'Contratado real CUC' => 'nullable|numeric',
            'act' => 'nullable|boolean',
            'Fecha entact' => 'nullable|date',
            'fecha salact' => 'nullable|date',
            'No correo' => 'nullable|string',
            'Añocorreo' => 'nullable|integer',
            'pie' => 'nullable|boolean',
            'Fecha Sol pie' => 'nullable|date',
            'Fecha aprob pie' => 'nullable|date',
            'Progexp' => 'nullable|boolean',
            'Ditec' => 'nullable|boolean',
            'Permiso esp' => 'nullable|boolean',
            'Fecha ultimo Dictamen' => 'nullable|date',
        ]);
        
        Solicitud::create($validated);
        
        return redirect()->route('solicitudes.index')
            ->with('success', 'Solicitud creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $solicitud = Solicitud::findOrFail($id);
        return view('solicitudes.show', compact('solicitud'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $solicitud = Solicitud::findOrFail($id);
        $clientes = Cliente::orderBy('Cliente')->get();
        $especialistas = Specialist::where('Activos', 1)->orderBy('Especialista')->get();
        $centrosBalance = BalanceCenter::where('Activos', 1)->orderBy('Centro Balance')->get();
        
        return view('solicitudes.edit', compact('solicitud', 'clientes', 'especialistas', 'centrosBalance'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);
        
        $validated = $request->validate([
            'No Solicitud' => 'required|unique:Solicitudes,No Solicitud,' . $solicitud->{'Id Solicitud'} . ',Id Solicitud',
            'Fecha Solicitud' => 'required|date',
            'Id Cliente' => 'required|exists:Nomenclador Clientes,Id Cliente',
            'Id Especialista' => 'required|exists:Nomenclador Especialistas,Id especialista',
            'Id Centro Balance' => 'required|exists:Nomenclador Centros Balance,Id Centro Balnce',
            'Observaciones' => 'nullable|string',
            'Decripción Solicitud' => 'nullable|string',
            'Fecha rec sol' => 'nullable|date',
            'Fecha acep aduana' => 'nullable|date',
            'Fecha acep esp' => 'nullable|date',
            'Asignado MCUC' => 'nullable|boolean',
            'Distribuido MCUC' => 'nullable|boolean',
            'Fecha Salida Mercado' => 'nullable|date',
            'Fecha primera oferta' => 'nullable|date',
            'Id OSDE' => 'nullable|integer',
            'Id Destino' => 'nullable|integer',
            'Id Clasificacion' => 'nullable|integer',
            'Id Tipo Operacion' => 'nullable|integer',
            'Id Linea Credito' => 'nullable|integer',
            'Id Fuentefinan' => 'nullable|integer',
            'Id Tipo prodg' => 'nullable|integer',
            'Dias Oferta' => 'nullable|integer',
            'añosol' => 'nullable|integer',
            'Observ Esp' => 'nullable|string',
            'Devuelta' => 'nullable|boolean',
            'Fecha Dev' => 'nullable|date',
            'Cancelada' => 'nullable|boolean',
            'Fecha Can' => 'nullable|date',
            'añofinan' => 'nullable|integer',
            'Pendiente Contratar' => 'nullable|boolean',
            'Contratado real CUC' => 'nullable|numeric',
            'act' => 'nullable|boolean',
            'Fecha entact' => 'nullable|date',
            'fecha salact' => 'nullable|date',
            'No correo' => 'nullable|string',
            'Añocorreo' => 'nullable|integer',
            'pie' => 'nullable|boolean',
            'Fecha Sol pie' => 'nullable|date',
            'Fecha aprob pie' => 'nullable|date',
            'Progexp' => 'nullable|boolean',
            'Ditec' => 'nullable|boolean',
            'Permiso esp' => 'nullable|boolean',
            'Fecha ultimo Dictamen' => 'nullable|date',
        ]);
        
        $solicitud->update($validated);
        
        return redirect()->route('solicitudes.index')
            ->with('success', 'Solicitud actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $solicitud = Solicitud::findOrFail($id);
        $solicitud->delete();
        
        return redirect()->route('solicitudes.index')
            ->with('success', 'Solicitud eliminada exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron solicitudes para eliminar.');
        }
        
        $count = Solicitud::whereIn('Id Solicitud', $ids)->delete();
        
        return redirect()->route('solicitudes.index')
            ->with('success', "Se eliminaron $count solicitudes correctamente.");
    }
    
    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Solicitud::with(['cliente', 'especialista', 'balanceCenter']);
        
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
        
        $solicitudes = $query->orderBy('Fecha Solicitud', 'desc')->get();
        
        return Excel::download(new SolicitudesExport($solicitudes), 'solicitudes.xlsx');
    }
    
    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Solicitud::with(['cliente', 'especialista', 'balanceCenter']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Solicitud', $ids);
            }
        }
        
        $solicitudes = $query->orderBy('Fecha Solicitud', 'desc')->get();
        
        $pdf = PDF::loadView('solicitudes.pdf', [
            'solicitudes' => $solicitudes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('solicitudes.pdf');
    }
    
    /**
     * Imprimir listado
     */
    public function print(Request $request)
    {
        $query = Solicitud::with(['cliente', 'especialista', 'balanceCenter']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Solicitud', $ids);
            }
        }
        
        $solicitudes = $query->orderBy('Fecha Solicitud', 'desc')->get();
        
        return view('solicitudes.print', [
            'solicitudes' => $solicitudes,
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
                $q->where('No Solicitud', 'like', "%{$search}%")
                  ->orWhere('Decripción Solicitud', 'like', "%{$search}%")
                  ->orWhere('Observ Esp', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('Id Cliente')) {
            $query->where('Id Cliente', $request->input('Id Cliente'));
        }
        
        if ($request->filled('Id Especialista')) {
            $query->where('Id Especialista', $request->input('Id Especialista'));
        }
        
        if ($request->filled('Id Centro Balance')) {
            $query->where('Id Centro Balance', $request->input('Id Centro Balance'));
        }
        
        if ($request->filled('Id OSDE')) {
            $query->where('Id OSDE', $request->input('Id OSDE'));
        }
        
        if ($request->filled('Id Destino')) {
            $query->where('Id Destino', $request->input('Id Destino'));
        }
        
        if ($request->filled('Id Clasificacion')) {
            $query->where('Id Clasificacion', $request->input('Id Clasificacion'));
        }
        
        if ($request->filled('Id Tipo Operacion')) {
            $query->where('Id Tipo Operacion', $request->input('Id Tipo Operacion'));
        }
        
        if ($request->filled('Devuelta')) {
            $query->where('Devuelta', $request->input('Devuelta'));
        }
        
        if ($request->filled('Cancelada')) {
            $query->where('Cancelada', $request->input('Cancelada'));
        }
        
        if ($request->filled('Pendiente Contratar')) {
            $query->where('Pendiente Contratar', $request->input('Pendiente Contratar'));
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Solicitud', '>=', $request->input('fecha_desde'));
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Solicitud', '<=', $request->input('fecha_hasta'));
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
        
        if ($request->filled('Id Cliente')) {
            $cliente = Cliente::find($request->input('Id Cliente'));
            $filtros[] = "Cliente: " . ($cliente ? $cliente->Cliente : 'Desconocido');
        }
        
        if ($request->filled('Id Especialista')) {
            $especialista = Specialist::find($request->input('Id Especialista'));
            $filtros[] = "Especialista: " . ($especialista ? $especialista->Especialista : 'Desconocido');
        }
        
        if ($request->filled('Id Centro Balance')) {
            $centro = BalanceCenter::find($request->input('Id Centro Balance'));
            $filtros[] = "Centro Balance: " . ($centro ? $centro->{'Centro Balance'} : 'Desconocido');
        }
        
        if ($request->filled('Id OSDE')) {
            $filtros[] = "ID OSDE: " . $request->input('Id OSDE');
        }
        
        if ($request->filled('Id Destino')) {
            $filtros[] = "ID Destino: " . $request->input('Id Destino');
        }
        
        if ($request->filled('Id Clasificacion')) {
            $filtros[] = "ID Clasificación: " . $request->input('Id Clasificacion');
        }
        
        if ($request->filled('Id Tipo Operacion')) {
            $filtros[] = "ID Tipo Operación: " . $request->input('Id Tipo Operacion');
        }
        
        if ($request->filled('Devuelta')) {
            $filtros[] = "Devuelta: " . ($request->input('Devuelta') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('Cancelada')) {
            $filtros[] = "Cancelada: " . ($request->input('Cancelada') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('Pendiente Contratar')) {
            $filtros[] = "Pendiente Contratar: " . ($request->input('Pendiente Contratar') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $filtros[] = "Fecha: " . $request->input('fecha_desde') . " a " . $request->input('fecha_hasta');
        } elseif ($request->filled('fecha_desde')) {
            $filtros[] = "Desde: " . $request->input('fecha_desde');
        } elseif ($request->filled('fecha_hasta')) {
            $filtros[] = "Hasta: " . $request->input('fecha_hasta');
        }
        
        return $filtros;
    }
}