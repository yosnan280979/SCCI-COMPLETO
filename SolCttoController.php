<?php

namespace App\Http\Controllers;

use App\Models\SolCtto;
use App\Models\Solicitud;
use App\Models\Contrato;
use App\Models\DatosSOE;
use Illuminate\Http\Request;
use App\Exports\SolCttoExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class SolCttoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SolCtto::with(['solicitud', 'contrato', 'datosSOE']);
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Observaciones', 'like', "%{$search}%")
                  ->orWhere('Id SOE', 'like', "%{$search}%")
                  ->orWhere('Id Clasificacion', 'like', "%{$search}%")
                  ->orWhere('Id Actualizado', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('Id_Solicitud')) {
            $query->where('Id Solicitud', $request->Id_Solicitud);
        }
        
        if ($request->filled('Id_Ctto')) {
            $query->where('Id Ctto', $request->Id_Ctto);
        }
        
        if ($request->filled('Id_SOE')) {
            $query->where('Id SOE', 'like', "%{$request->Id_SOE}%");
        }
        
        if ($request->filled('Id_Clasificacion')) {
            $query->where('Id Clasificacion', 'like', "%{$request->Id_Clasificacion}%");
        }
        
        if ($request->filled('Id_Actualizado')) {
            $query->where('Id Actualizado', 'like', "%{$request->Id_Actualizado}%");
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $items = $query->paginate(20)->appends($request->query());
        
        // Obtener datos para filtros
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $contratos = Contrato::orderBy('No Ctto')->get();
        
        return view('sol-ctto.index', compact('items', 'solicitudes', 'contratos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $contratos = Contrato::orderBy('No Ctto')->get();
        $soes = DatosSOE::orderBy('Id SOE', 'desc')->get();
        
        return view('sol-ctto.create', compact('solicitudes', 'contratos', 'soes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_Solicitud' => 'nullable|exists:Solicitudes,Id Solicitud',
            'Id_Ctto' => 'nullable|exists:Contratos,Id Ctto',
            'Id_SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'Observaciones' => 'nullable|string|max:500',
            'Id_Clasificacion' => 'nullable|integer',
            'Id_Actualizado' => 'nullable|integer',
            'Valor_Real_Sol' => 'nullable|numeric',
        ]);

        // Mapear nombres del formulario a nombres de columna reales (con espacios)
        $data = [
            'Id Solicitud' => $validated['Id_Solicitud'] ?? null,
            'Id Ctto' => $validated['Id_Ctto'] ?? null,
            'Id SOE' => $validated['Id_SOE'] ?? null,
            'Observaciones' => $validated['Observaciones'] ?? null,
            'Id Clasificacion' => $validated['Id_Clasificacion'] ?? null,
            'Id Actualizado' => $validated['Id_Actualizado'] ?? null,
            'Valor Real Sol' => $validated['Valor_Real_Sol'] ?? null,
        ];

        SolCtto::create($data);
        
        return redirect()->route('sol-ctto.index')
            ->with('success', 'Relación Solicitud-Contrato creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = SolCtto::with(['solicitud', 'contrato', 'datosSOE'])->findOrFail($id);
        return view('sol-ctto.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = SolCtto::findOrFail($id);
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $contratos = Contrato::orderBy('No Ctto')->get();
        $soes = DatosSOE::orderBy('Id SOE', 'desc')->get();
        
        return view('sol-ctto.edit', compact('item', 'solicitudes', 'contratos', 'soes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = SolCtto::findOrFail($id);
        
        $validated = $request->validate([
            'Id_Solicitud' => 'nullable|exists:Solicitudes,Id Solicitud',
            'Id_Ctto' => 'nullable|exists:Contratos,Id Ctto',
            'Id_SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'Observaciones' => 'nullable|string|max:500',
            'Id_Clasificacion' => 'nullable|integer',
            'Id_Actualizado' => 'nullable|integer',
            'Valor_Real_Sol' => 'nullable|numeric',
        ]);

        // Mapear nombres del formulario a nombres de columna reales (con espacios)
        $data = [
            'Id Solicitud' => $validated['Id_Solicitud'] ?? null,
            'Id Ctto' => $validated['Id_Ctto'] ?? null,
            'Id SOE' => $validated['Id_SOE'] ?? null,
            'Observaciones' => $validated['Observaciones'] ?? null,
            'Id Clasificacion' => $validated['Id_Clasificacion'] ?? null,
            'Id Actualizado' => $validated['Id_Actualizado'] ?? null,
            'Valor Real Sol' => $validated['Valor_Real_Sol'] ?? null,
        ];

        $item->update($data);
        
        return redirect()->route('sol-ctto.index')
            ->with('success', 'Relación Solicitud-Contrato actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = SolCtto::findOrFail($id);
        $item->delete();
        
        return redirect()->route('sol-ctto.index')
            ->with('success', 'Relación Solicitud-Contrato eliminada exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron relaciones para eliminar.');
        }
        
        $count = SolCtto::whereIn('Id', $ids)->delete();
        
        return redirect()->route('sol-ctto.index')
            ->with('success', "Se eliminaron $count relaciones correctamente.");
    }

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = SolCtto::with(['solicitud', 'contrato', 'datosSOE']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        return Excel::download(new SolCttoExport($items), 'sol-ctto.xlsx');
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = SolCtto::with(['solicitud', 'contrato', 'datosSOE']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        $pdf = PDF::loadView('sol-ctto.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('sol-ctto.pdf');
    }

    /**
     * Imprimir listado
     */
    public function print(Request $request)
    {
        $query = SolCtto::with(['solicitud', 'contrato', 'datosSOE']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        return view('sol-ctto.print', [
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
                $q->where('Observaciones', 'like', "%{$search}%")
                  ->orWhere('Id SOE', 'like', "%{$search}%")
                  ->orWhere('Id Clasificacion', 'like', "%{$search}%")
                  ->orWhere('Id Actualizado', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('Id_Solicitud')) {
            $query->where('Id Solicitud', $request->Id_Solicitud);
        }
        
        if ($request->filled('Id_Ctto')) {
            $query->where('Id Ctto', $request->Id_Ctto);
        }
        
        if ($request->filled('Id_SOE')) {
            $query->where('Id SOE', 'like', "%{$request->Id_SOE}%");
        }
        
        if ($request->filled('Id_Clasificacion')) {
            $query->where('Id Clasificacion', 'like', "%{$request->Id_Clasificacion}%");
        }
        
        if ($request->filled('Id_Actualizado')) {
            $query->where('Id Actualizado', 'like', "%{$request->Id_Actualizado}%");
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($request->input('sort_by'), $sortDir);
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
        
        if ($request->filled('Id_Solicitud')) {
            $solicitud = Solicitud::find($request->Id_Solicitud);
            $filtros[] = "Solicitud: " . ($solicitud ? $solicitud->{'No Solicitud'} . " (ID: {$request->Id_Solicitud})" : $request->Id_Solicitud);
        }
        
        if ($request->filled('Id_Ctto')) {
            $contrato = Contrato::find($request->Id_Ctto);
            $filtros[] = "Contrato: " . ($contrato ? $contrato->{'No Ctto'} . " (ID: {$request->Id_Ctto})" : $request->Id_Ctto);
        }
        
        if ($request->filled('Id_SOE')) {
            $filtros[] = "ID SOE: " . $request->Id_SOE;
        }
        
        if ($request->filled('Id_Clasificacion')) {
            $filtros[] = "ID Clasificación: " . $request->Id_Clasificacion;
        }
        
        if ($request->filled('Id_Actualizado')) {
            $filtros[] = "ID Actualizado: " . $request->Id_Actualizado;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->input('sort_by') . " (" . ($sortDir == 'desc' ? 'descendente' : 'ascendente') . ")";
        }
        
        return $filtros;
    }
}