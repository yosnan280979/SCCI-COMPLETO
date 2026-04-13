<?php

namespace App\Http\Controllers;

use App\Models\TipoProductoProveedor;
use App\Models\TipoProducto;
use App\Models\Provider;
use Illuminate\Http\Request;
use App\Exports\TipoProductoProveedorExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Illuminate\Support\Facades\DB;

class TipoProductoProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TipoProductoProveedor::with(['tipoProducto', 'proveedor']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Datos', 'like', "%{$search}%")
                  ->orWhereHas('tipoProducto', function($q) use ($search) {
                      $q->where('Tipo Producto', 'like', "%{$search}%");
                  })
                  ->orWhereHas('proveedor', function($q) use ($search) {
                      $q->where('Proveedor', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('Tipo_Producto')) {
            $query->where('Id Tipo Producto', $request->input('Tipo_Producto'));
        }
        
        if ($request->filled('Proveedor')) {
            $query->where('Id Proveedor', $request->input('Proveedor'));
        }
        
        if ($request->filled('Datos')) {
            $query->where('Datos', 'like', "%{$request->input('Datos')}%");
        }
        
        // Ordenamiento - CORREGIDO: usando los mismos nombres de parámetros que la vista
        $sortBy = $request->input('sort_by', 'Id Tipo Producto');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $items = $query->paginate(25)->appends($request->query());
        
        // Obtener datos para los filtros
        $tiposProductos = TipoProducto::orderBy('Tipo Producto')->get();
        $proveedores = Provider::where('Activo', 1)->orderBy('Proveedor')->get();
        
        return view('tipo-producto-proveedor.index', compact('items', 'tiposProductos', 'proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tiposProductos = TipoProducto::orderBy('Tipo Producto')->get();
        $proveedores = Provider::where('Activo', 1)->orderBy('Proveedor')->get();
        
        return view('tipo-producto-proveedor.create', compact('tiposProductos', 'proveedores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Tipo Producto' => 'required|exists:Nomenclador Tipos Productos,Id Tipo Producto',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Datos' => 'nullable|string',
        ]);
        
        // Verificar si ya existe esta combinación
        $exists = TipoProductoProveedor::where('Id Tipo Producto', $validated['Id Tipo Producto'])
            ->where('Id Proveedor', $validated['Id Proveedor'])
            ->exists();
        
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Esta combinación de Tipo Producto y Proveedor ya existe.');
        }
        
        TipoProductoProveedor::create($validated);
        
        return redirect()->route('tipo-producto-proveedor.index')
            ->with('success', 'Relación creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($idTipoProducto, $idProveedor)
    {
        $item = TipoProductoProveedor::with(['tipoProducto', 'proveedor'])
            ->where('Id Tipo Producto', $idTipoProducto)
            ->where('Id Proveedor', $idProveedor)
            ->firstOrFail();
        
        return view('tipo-producto-proveedor.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($idTipoProducto, $idProveedor)
    {
        $item = TipoProductoProveedor::where('Id Tipo Producto', $idTipoProducto)
            ->where('Id Proveedor', $idProveedor)
            ->firstOrFail();
        
        $tiposProductos = TipoProducto::orderBy('Tipo Producto')->get();
        $proveedores = Provider::where('Activo', 1)->orderBy('Proveedor')->get();
        
        return view('tipo-producto-proveedor.edit', compact('item', 'tiposProductos', 'proveedores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $idTipoProducto, $idProveedor)
    {
        $item = TipoProductoProveedor::where('Id Tipo Producto', $idTipoProducto)
            ->where('Id Proveedor', $idProveedor)
            ->firstOrFail();
        
        $validated = $request->validate([
            'Id Tipo Producto' => 'required|exists:Nomenclador Tipos Productos,Id Tipo Producto',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Datos' => 'nullable|string',
        ]);
        
        // Verificar si la nueva combinación ya existe (excluyendo el actual)
        $exists = TipoProductoProveedor::where('Id Tipo Producto', $validated['Id Tipo Producto'])
            ->where('Id Proveedor', $validated['Id Proveedor'])
            ->where('Id Tipo Producto', '!=', $idTipoProducto)
            ->where('Id Proveedor', '!=', $idProveedor)
            ->exists();
        
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Esta combinación de Tipo Producto y Proveedor ya existe.');
        }
        
        $item->update($validated);
        
        return redirect()->route('tipo-producto-proveedor.index')
            ->with('success', 'Relación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idTipoProducto, $idProveedor)
    {
        $item = TipoProductoProveedor::where('Id Tipo Producto', $idTipoProducto)
            ->where('Id Proveedor', $idProveedor)
            ->firstOrFail();
        
        $item->delete();
        
        return redirect()->route('tipo-producto-proveedor.index')
            ->with('success', 'Relación eliminada exitosamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = TipoProductoProveedor::with(['tipoProducto', 'proveedor']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            // Las IDs vienen como "idTipoProducto-idProveedor"
            $pairs = explode(',', $selected);
            $pairs = array_filter($pairs);
            
            if (!empty($pairs)) {
                $query->where(function($q) use ($pairs) {
                    foreach ($pairs as $pair) {
                        list($idTipo, $idProv) = explode('-', $pair);
                        $q->orWhere(function($subq) use ($idTipo, $idProv) {
                            $subq->where('Id Tipo Producto', $idTipo)
                                 ->where('Id Proveedor', $idProv);
                        });
                    }
                });
            }
        }
        
        $items = $query->get();
        
        return Excel::download(new TipoProductoProveedorExport($items), 'tipo-producto-proveedor.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = TipoProductoProveedor::with(['tipoProducto', 'proveedor']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $pairs = explode(',', $selected);
            $pairs = array_filter($pairs);
            
            if (!empty($pairs)) {
                $query->where(function($q) use ($pairs) {
                    foreach ($pairs as $pair) {
                        list($idTipo, $idProv) = explode('-', $pair);
                        $q->orWhere(function($subq) use ($idTipo, $idProv) {
                            $subq->where('Id Tipo Producto', $idTipo)
                                 ->where('Id Proveedor', $idProv);
                        });
                    }
                });
            }
        }
        
        $items = $query->get();
        
        $pdf = PDF::loadView('tipo-producto-proveedor.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('tipo-producto-proveedor.pdf');
    }
    
    public function print(Request $request)
    {
        $query = TipoProductoProveedor::with(['tipoProducto', 'proveedor']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $pairs = explode(',', $selected);
            $pairs = array_filter($pairs);
            
            if (!empty($pairs)) {
                $query->where(function($q) use ($pairs) {
                    foreach ($pairs as $pair) {
                        list($idTipo, $idProv) = explode('-', $pair);
                        $q->orWhere(function($subq) use ($idTipo, $idProv) {
                            $subq->where('Id Tipo Producto', $idTipo)
                                 ->where('Id Proveedor', $idProv);
                        });
                    }
                });
            }
        }
        
        $items = $query->get();
        
        return view('tipo-producto-proveedor.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'regex:/^\d+-\d+$/'
        ]);
        
        $count = 0;
        foreach ($request->ids as $idPair) {
            list($idTipo, $idProv) = explode('-', $idPair);
            
            $item = TipoProductoProveedor::where('Id Tipo Producto', $idTipo)
                ->where('Id Proveedor', $idProv)
                ->first();
            
            if ($item) {
                $item->delete();
                $count++;
            }
        }
        
        return redirect()->route('tipo-producto-proveedor.index')
            ->with('success', "Se eliminaron $count relaciones correctamente.");
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Datos', 'like', "%{$search}%")
                  ->orWhereHas('tipoProducto', function($q) use ($search) {
                      $q->where('Tipo Producto', 'like', "%{$search}%");
                  })
                  ->orWhereHas('proveedor', function($q) use ($search) {
                      $q->where('Proveedor', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('Tipo_Producto')) {
            $query->where('Id Tipo Producto', $request->input('Tipo_Producto'));
        }
        
        if ($request->filled('Proveedor')) {
            $query->where('Id Proveedor', $request->input('Proveedor'));
        }
        
        if ($request->filled('Datos')) {
            $query->where('Datos', 'like', "%{$request->input('Datos')}%");
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($request->input('sort_by'), $sortDir);
        } else {
            $query->orderBy('Id Tipo Producto', 'desc');
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
        
        if ($request->filled('Tipo_Producto')) {
            $tipo = TipoProducto::find($request->input('Tipo_Producto'));
            $filtros[] = "Tipo Producto: " . ($tipo ? $tipo->{'Tipo Producto'} : 'Desconocido');
        }
        
        if ($request->filled('Proveedor')) {
            $proveedor = Provider::find($request->input('Proveedor'));
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor : 'Desconocido');
        }
        
        if ($request->filled('Datos')) {
            $filtros[] = "Datos: " . $request->Datos;
        }
        
        return $filtros;
    }
}