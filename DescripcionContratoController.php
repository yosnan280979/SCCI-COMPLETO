<?php

namespace App\Http\Controllers;

use App\Models\DescripcionContrato;
use App\Models\Solicitud;
use App\Models\Contrato;
use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DescripcionContratoExport;
use PDF;
use DB;

class DescripcionContratoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DescripcionContrato::with(['solicitud', 'contrato', 'moneda']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Producto', 'like', "%{$search}%")
                  ->orWhere('UM', 'like', "%{$search}%");
            });
        }
        
        // Filtro de solicitud - CORREGIDO: usar el nombre real de la columna
        if ($request->filled('Id_Solicitud')) {
            $query->where('Id Solicitud', $request->input('Id_Solicitud'));
        }
        
        // Filtro de contrato - CORREGIDO: usar el nombre real de la columna
        if ($request->filled('Id_Ctto')) {
            $query->where('Id Ctto', $request->input('Id_Ctto'));
        }
        
        if ($request->filled('UM')) {
            $query->where('UM', $request->input('UM'));
        }
        
        if ($request->filled('precio_min')) {
            $query->where('Precio CUC', '>=', $request->input('precio_min'));
        }
        
        if ($request->filled('precio_max')) {
            $query->where('Precio CUC', '<=', $request->input('precio_max'));
        }
        
        // Ordenación - CORREGIDO: mapear nombres con guiones bajos a nombres reales con espacios
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        
        // Mapear nombres de ordenación a nombres reales de columnas
        $columnMap = [
            'Id' => 'Id',
            'Id_Solicitud' => 'Id Solicitud',
            'Id_Ctto' => 'Id Ctto',
            'Producto' => 'Producto',
            'Cantidad' => 'Cantidad',
            'Precio CUC' => 'Precio CUC',
        ];
        
        // Usar el nombre real de la columna para ordenar
        $realColumnName = $columnMap[$sortBy] ?? $sortBy;
        $query->orderBy($realColumnName, $sortDir);
        
        // Paginación
        $descripcionContratos = $query->paginate(25)->appends($request->query());
        
        // Obtener datos para filtros
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $contratos = Contrato::orderBy('No Ctto')->get();
        
        // Obtener unidades de medida únicas
        $unidadesMedida = DescripcionContrato::select('UM')
            ->whereNotNull('UM')
            ->distinct()
            ->orderBy('UM')
            ->get();
        
        return view('descripcion-contrato.index', compact(
            'descripcionContratos', 
            'solicitudes', 
            'contratos', 
            'unidadesMedida'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $contratos = Contrato::orderBy('No Ctto')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        $unidadesMedida = DescripcionContrato::select('UM')
            ->whereNotNull('UM')
            ->distinct()
            ->orderBy('UM')
            ->get();
        
        return view('descripcion-contrato.create', compact(
            'solicitudes', 
            'contratos', 
            'monedas', 
            'unidadesMedida'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Solicitud' => 'nullable|exists:Solicitudes,Id Solicitud',
            'Id Ctto' => 'nullable|exists:Contratos,Id Ctto',
            'Producto' => 'required|string|max:250',
            'UM' => 'required|string|max:12',
            'Cantidad' => 'required|numeric|min:0',
            'Precio CUC' => 'nullable|numeric|min:0',
            'Precio Mon Prov' => 'nullable|numeric|min:0',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
        ]);
        
        // Asegurar que los nombres de campo coincidan con la base de datos
        $validated['Id Solicitud'] = $validated['Id Solicitud'] ?? null;
        $validated['Id Ctto'] = $validated['Id Ctto'] ?? null;
        $validated['Id Moneda'] = $validated['Id Moneda'] ?? null;
        
        DescripcionContrato::create($validated);
        
        return redirect()->route('descripcion-contrato.index')
            ->with('success', 'Descripción de contrato creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $descripcionContrato = DescripcionContrato::with(['solicitud', 'contrato', 'moneda'])
            ->findOrFail($id);
        
        return view('descripcion-contrato.show', compact('descripcionContrato'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $descripcionContrato = DescripcionContrato::findOrFail($id);
        $solicitudes = Solicitud::orderBy('No Solicitud')->get();
        $contratos = Contrato::orderBy('No Ctto')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        $unidadesMedida = DescripcionContrato::select('UM')
            ->whereNotNull('UM')
            ->distinct()
            ->orderBy('UM')
            ->get();
        
        return view('descripcion-contrato.edit', compact(
            'descripcionContrato',
            'solicitudes', 
            'contratos', 
            'monedas', 
            'unidadesMedida'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $descripcionContrato = DescripcionContrato::findOrFail($id);
        
        $validated = $request->validate([
            'Id Solicitud' => 'nullable|exists:Solicitudes,Id Solicitud',
            'Id Ctto' => 'nullable|exists:Contratos,Id Ctto',
            'Producto' => 'required|string|max:250',
            'UM' => 'required|string|max:12',
            'Cantidad' => 'required|numeric|min:0',
            'Precio CUC' => 'nullable|numeric|min:0',
            'Precio Mon Prov' => 'nullable|numeric|min:0',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
        ]);
        
        // Asegurar que los nombres de campo coincidan con la base de datos
        $validated['Id Solicitud'] = $validated['Id Solicitud'] ?? null;
        $validated['Id Ctto'] = $validated['Id Ctto'] ?? null;
        $validated['Id Moneda'] = $validated['Id Moneda'] ?? null;
        
        $descripcionContrato->update($validated);
        
        return redirect()->route('descripcion-contrato.index')
            ->with('success', 'Descripción de contrato actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $descripcionContrato = DescripcionContrato::findOrFail($id);
        $descripcionContrato->delete();
        
        return redirect()->route('descripcion-contrato.index')
            ->with('success', 'Descripción de contrato eliminada exitosamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = DescripcionContrato::with(['solicitud', 'contrato', 'moneda']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $descripcionContratos = $query->get();
        
        return Excel::download(new DescripcionContratoExport($descripcionContratos), 'descripcion-contratos.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = DescripcionContrato::with(['solicitud', 'contrato', 'moneda']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $descripcionContratos = $query->get();
        
        // Calcular totales
        $totalCantidad = $descripcionContratos->sum('Cantidad');
        $totalPrecioCUC = $descripcionContratos->sum('Precio CUC');
        
        $pdf = PDF::loadView('descripcion-contrato.pdf', [
            'descripcionContratos' => $descripcionContratos,
            'totalCantidad' => $totalCantidad,
            'totalPrecioCUC' => $totalPrecioCUC,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('descripcion-contratos.pdf');
    }
    
    public function print(Request $request)
    {
        $query = DescripcionContrato::with(['solicitud', 'contrato', 'moneda']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $descripcionContratos = $query->get();
        
        // Calcular totales
        $totalCantidad = $descripcionContratos->sum('Cantidad');
        $totalPrecioCUC = $descripcionContratos->sum('Precio CUC');
        
        return view('descripcion-contrato.print', [
            'descripcionContratos' => $descripcionContratos,
            'totalCantidad' => $totalCantidad,
            'totalPrecioCUC' => $totalPrecioCUC,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Descripcion Contrato,Id'
        ]);
        
        $ids = $request->ids;
        $count = DescripcionContrato::whereIn('Id', $ids)->delete();
        
        return redirect()->route('descripcion-contrato.index')
            ->with('success', "Se eliminaron $count descripciones de contrato correctamente.");
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
        
        if ($request->filled('Id_Solicitud')) {
            $query->where('Id Solicitud', $request->input('Id_Solicitud'));
        }
        
        if ($request->filled('Id_Ctto')) {
            $query->where('Id Ctto', $request->input('Id_Ctto'));
        }
        
        if ($request->filled('UM')) {
            $query->where('UM', $request->input('UM'));
        }
        
        if ($request->filled('precio_min')) {
            $query->where('Precio CUC', '>=', $request->input('precio_min'));
        }
        
        if ($request->filled('precio_max')) {
            $query->where('Precio CUC', '<=', $request->input('precio_max'));
        }
        
        // Ordenación con mapeo de columnas
        if ($request->filled('sort_by')) {
            $sortBy = $request->input('sort_by');
            $sortDir = $request->input('sort_dir', 'desc');
            
            // Mapear nombres de ordenación a nombres reales de columnas
            $columnMap = [
                'Id' => 'Id',
                'Id_Solicitud' => 'Id Solicitud',
                'Id_Ctto' => 'Id Ctto',
                'Producto' => 'Producto',
                'Cantidad' => 'Cantidad',
                'Precio CUC' => 'Precio CUC',
            ];
            
            $realColumnName = $columnMap[$sortBy] ?? $sortBy;
            $query->orderBy($realColumnName, $sortDir);
        } else {
            $query->orderBy('Id', 'desc');
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
            $solicitud = Solicitud::find($request->input('Id_Solicitud'));
            $filtros[] = "Solicitud: " . ($solicitud ? $solicitud->{'No Solicitud'} : 'Desconocido');
        }
        
        if ($request->filled('Id_Ctto')) {
            $contrato = Contrato::find($request->input('Id_Ctto'));
            $filtros[] = "Contrato: " . ($contrato ? $contrato->{'No Ctto'} : 'Desconocido');
        }
        
        if ($request->filled('UM')) {
            $filtros[] = "Unidad de Medida: " . $request->UM;
        }
        
        if ($request->filled('precio_min')) {
            $filtros[] = "Precio mínimo: " . $request->precio_min;
        }
        
        if ($request->filled('precio_max')) {
            $filtros[] = "Precio máximo: " . $request->precio_max;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}