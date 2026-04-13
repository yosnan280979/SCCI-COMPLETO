<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\Pais;
use App\Models\TipoProveedor;
use App\Models\Currency;
use App\Models\TipoProducto;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProvidersExport;
use PDF;

class ProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Provider::with('tipoProducto'); // <-- CARGA LA RELACIÓN
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Proveedor', 'like', "%{$search}%")
                  ->orWhere('No Exp', 'like', "%{$search}%")
                  ->orWhere('Correo', 'like', "%{$search}%")
                  ->orWhere('Codigo MINCEX', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('pais')) {
            $query->where('País', $request->pais);
        }
        
        if ($request->filled('activo')) {
            $query->where('Activo', $request->activo);
        }
        
        if ($request->filled('tipo_proveedor')) {
            $query->where('Id Tipo Prov', $request->tipo_proveedor);
        }
        
        if ($request->filled('oficina_cuba')) {
            $query->where('Oficina Cuba', $request->oficina_cuba);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Proveedor');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $providers = $query->paginate(20)->appends($request->query());
        
        // Obtener datos para filtros
        $paises = Pais::orderBy('País')->get();
        $tiposProveedor = TipoProveedor::orderBy('Tipo Proveedor')->get();
        
        return view('nomencladores.providers.index', compact('providers', 'paises', 'tiposProveedor'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $paises = Pais::orderBy('País')->get();
        $tiposProveedor = TipoProveedor::orderBy('Tipo Proveedor')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        $tiposProducto = TipoProducto::orderBy('Tipo Producto')->get();
        
        return view('nomencladores.providers.create', compact('paises', 'tiposProveedor', 'monedas', 'tiposProducto'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'No Exp' => 'nullable|string|max:255',
            'Proveedor' => 'required|string|max:255',
            'País' => 'nullable|integer',
            'Activo' => 'nullable|boolean',
            'Correo' => 'nullable|email|max:255',
            'Codigo MINCEX' => 'nullable|string|max:255',
            'Fecha CC' => 'nullable|date',
            'Id Tipo Prov' => 'nullable|integer',
            'Oficina Cuba' => 'nullable|boolean',
            'Correo Cuba' => 'nullable|email|max:255',
            'Direccion Cuba' => 'nullable|string|max:500',
            'Direccion Cmatriz' => 'nullable|string|max:500',
            'Correo Cmatriz' => 'nullable|email|max:255',
            'Telef Cuba' => 'nullable|string|max:50',
            'Telef Cmatriz' => 'nullable|string|max:50',
            'Fax Cuba' => 'nullable|string|max:50',
            'Fax Cmatriz' => 'nullable|string|max:50',
            'Productos' => 'nullable|string',
            'Vigencia CC' => 'nullable|date',
            'No registro CC' => 'nullable|string|max:255',
            'Siglas' => 'nullable|string|max:50',
            'Logo' => 'nullable|boolean',
            'Sitio Web' => 'nullable|url|max:255',
            'Fecha Fundacion' => 'nullable|date',
            'Capital Social' => 'nullable|numeric',
            'Id Moneda' => 'nullable|integer',
            'Codigo Postal Cmatriz' => 'nullable|string|max:20',
            'Codigo Postal Cuba' => 'nullable|string|max:20',
            'Registro Mercantil' => 'nullable|string|max:255',
            'Fecha RM' => 'nullable|date',
            'Fecha V RM' => 'nullable|date',
            'Region Cmatriz' => 'nullable|string|max:255',
            'Ciudad cmatriz' => 'nullable|string|max:255',
            'No Esc Cons' => 'nullable|string|max:50',
            'Fecha Esc Cons' => 'nullable|date',
            'Notario' => 'nullable|string|max:255',
            'Fecha confa1' => 'nullable|date',
            'Fecha aval BCuba' => 'nullable|date',
            'Fecha aval Bext' => 'nullable|date',
            'Año ultbalance' => 'nullable|integer',
            'Fecha alta comite' => 'nullable|date',
            'Acta alta comite' => 'nullable|string|max:255',
            'Acuerdo alta comite' => 'nullable|string|max:255',
            'Fecha alta consejo' => 'nullable|date',
            'Acta alta consejo' => 'nullable|string|max:255',
            'Acuerdo alta consejo' => 'nullable|string|max:255',
            'Fecha baja consejo' => 'nullable|date',
            'Acta baja consejo' => 'nullable|string|max:255',
            'Acuerdo baja consejo' => 'nullable|string|max:255',
            'temp Observ' => 'nullable|string',
            'Id Tipo Producto' => 'nullable|integer|exists:Nomenclador Tipos Productos,Id Tipo Producto',
        ]);
        
        Provider::create($validated);
        
        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $provider = Provider::with('tipoProducto')->findOrFail($id);
        return view('nomencladores.providers.show', compact('provider'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $provider = Provider::findOrFail($id);
        $paises = Pais::orderBy('País')->get();
        $tiposProveedor = TipoProveedor::orderBy('Tipo Proveedor')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        $tiposProducto = TipoProducto::orderBy('Tipo Producto')->get();
        
        return view('nomencladores.providers.edit', compact('provider', 'paises', 'tiposProveedor', 'monedas', 'tiposProducto'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $provider = Provider::findOrFail($id);
        
        $validated = $request->validate([
            'No Exp' => 'nullable|string|max:255',
            'Proveedor' => 'required|string|max:255',
            'País' => 'nullable|integer',
            'Activo' => 'nullable|boolean',
            'Correo' => 'nullable|email|max:255',
            'Codigo MINCEX' => 'nullable|string|max:255',
            'Fecha CC' => 'nullable|date',
            'Id Tipo Prov' => 'nullable|integer',
            'Oficina Cuba' => 'nullable|boolean',
            'Correo Cuba' => 'nullable|email|max:255',
            'Direccion Cuba' => 'nullable|string|max:500',
            'Direccion Cmatriz' => 'nullable|string|max:500',
            'Correo Cmatriz' => 'nullable|email|max:255',
            'Telef Cuba' => 'nullable|string|max:50',
            'Telef Cmatriz' => 'nullable|string|max:50',
            'Fax Cuba' => 'nullable|string|max:50',
            'Fax Cmatriz' => 'nullable|string|max:50',
            'Productos' => 'nullable|string',
            'Vigencia CC' => 'nullable|date',
            'No registro CC' => 'nullable|string|max:255',
            'Siglas' => 'nullable|string|max:50',
            'Logo' => 'nullable|boolean',
            'Sitio Web' => 'nullable|url|max:255',
            'Fecha Fundacion' => 'nullable|date',
            'Capital Social' => 'nullable|numeric',
            'Id Moneda' => 'nullable|integer',
            'Codigo Postal Cmatriz' => 'nullable|string|max:20',
            'Codigo Postal Cuba' => 'nullable|string|max:20',
            'Registro Mercantil' => 'nullable|string|max:255',
            'Fecha RM' => 'nullable|date',
            'Fecha V RM' => 'nullable|date',
            'Region Cmatriz' => 'nullable|string|max:255',
            'Ciudad cmatriz' => 'nullable|string|max:255',
            'No Esc Cons' => 'nullable|string|max:50',
            'Fecha Esc Cons' => 'nullable|date',
            'Notario' => 'nullable|string|max:255',
            'Fecha confa1' => 'nullable|date',
            'Fecha aval BCuba' => 'nullable|date',
            'Fecha aval Bext' => 'nullable|date',
            'Año ultbalance' => 'nullable|integer',
            'Fecha alta comite' => 'nullable|date',
            'Acta alta comite' => 'nullable|string|max:255',
            'Acuerdo alta comite' => 'nullable|string|max:255',
            'Fecha alta consejo' => 'nullable|date',
            'Acta alta consejo' => 'nullable|string|max:255',
            'Acuerdo alta consejo' => 'nullable|string|max:255',
            'Fecha baja consejo' => 'nullable|date',
            'Acta baja consejo' => 'nullable|string|max:255',
            'Acuerdo baja consejo' => 'nullable|string|max:255',
            'temp Observ' => 'nullable|string',
            'Id Tipo Producto' => 'nullable|integer|exists:Nomenclador Tipos Productos,Id Tipo Producto',
        ]);
        
        $provider->update($validated);
        
        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $provider = Provider::findOrFail($id);
        $provider->delete();
        
        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor eliminado exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron proveedores para eliminar.');
        }
        
        $count = Provider::whereIn('Id Proveedor', $ids)->delete();
        
        return redirect()->route('proveedores.index')
            ->with('success', "Se eliminaron $count proveedores correctamente.");
    }
    
    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Provider::with('tipoProducto');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Proveedor', $ids);
            }
        }
        
        $providers = $query->orderBy('Id Proveedor')->get();
        
        return Excel::download(new ProvidersExport($providers), 'proveedores.xlsx');
    }
    
    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Provider::with('tipoProducto');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Proveedor', $ids);
            }
        }
        
        $providers = $query->orderBy('Id Proveedor')->get();
        
        $pdf = PDF::loadView('nomencladores.providers.pdf', [
            'providers' => $providers,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('proveedores.pdf');
    }
    
    /**
     * Imprimir listado
     */
    public function print(Request $request)
    {
        $query = Provider::with('tipoProducto');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Proveedor', $ids);
            }
        }
        
        $providers = $query->orderBy('Id Proveedor')->get();
        
        return view('nomencladores.providers.print', [
            'providers' => $providers,
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
                $q->where('Proveedor', 'like', "%{$search}%")
                  ->orWhere('No Exp', 'like', "%{$search}%")
                  ->orWhere('Correo', 'like', "%{$search}%")
                  ->orWhere('Codigo MINCEX', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('pais')) {
            $query->where('País', $request->pais);
        }
        
        if ($request->filled('activo')) {
            $query->where('Activo', $request->activo);
        }
        
        if ($request->filled('tipo_proveedor')) {
            $query->where('Id Tipo Prov', $request->tipo_proveedor);
        }
        
        if ($request->filled('oficina_cuba')) {
            $query->where('Oficina Cuba', $request->oficina_cuba);
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
        
        if ($request->filled('pais')) {
            $pais = Pais::find($request->pais);
            $filtros[] = "País: " . ($pais ? $pais->País : $request->pais);
        }
        
        if ($request->filled('activo')) {
            $filtros[] = "Estado: " . ($request->activo == '1' ? 'Activo' : 'Inactivo');
        }
        
        if ($request->filled('tipo_proveedor')) {
            $tipo = TipoProveedor::find($request->tipo_proveedor);
            $filtros[] = "Tipo Proveedor: " . ($tipo ? $tipo->{'Tipo Proveedor'} : $request->tipo_proveedor);
        }
        
        if ($request->filled('oficina_cuba')) {
            $filtros[] = "Oficina Cuba: " . ($request->oficina_cuba == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('selected_ids')) {
            $ids = explode(',', $request->selected_ids);
            $count = count($ids);
            $filtros[] = "Registros seleccionados: {$count}";
        }
        
        return $filtros;
    }
}