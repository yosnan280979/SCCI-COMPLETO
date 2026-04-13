<?php

namespace App\Http\Controllers;

use App\Models\ProveedorMarca;
use App\Models\Provider;
use Illuminate\Http\Request;
use App\Exports\ProveedorMarcasExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ProveedorMarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProveedorMarca::with('proveedor');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Marca', 'like', "%{$search}%")
                  ->orWhere('Productos', 'like', "%{$search}%")
                  ->orWhereHas('proveedor', function($q2) use ($search) {
                      $q2->where('Proveedor', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('Proveedor')) {
            $query->where('Id proveedor', $request->input('Proveedor'));
        }
        
        if ($request->filled('Marca')) {
            $query->where('Marca', 'like', "%{$request->input('Marca')}%");
        }
        
        if ($request->filled('Productos')) {
            $query->where('Productos', 'like', "%{$request->input('Productos')}%");
        }
        
        // Filtro por vencimiento
        if ($request->filled('vencimiento_desde')) {
            $query->where('Vencimiento', '>=', $request->input('vencimiento_desde'));
        }
        
        if ($request->filled('vencimiento_hasta')) {
            $query->where('Vencimiento', '<=', $request->input('vencimiento_hasta'));
        }
        
        // Ordenación - CORREGIDO
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $items = $query->paginate(25)->appends($request->query());
        
        // Obtener proveedores para el filtro
        $proveedores = Provider::where('Activo', 1)
            ->orderBy('Proveedor')
            ->get();
        
        return view('proveedores-marcas.index', compact('items', 'proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener proveedores activos
        $proveedores = Provider::where('Activo', 1)
            ->orderBy('Proveedor')
            ->get();
        
        return view('proveedores-marcas.create', compact('proveedores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Marca' => 'required|string|max:50',
            'Productos' => 'nullable|string|max:255',
            'Vencimiento' => 'nullable|date',
        ]);
        
        ProveedorMarca::create($validated);
        
        return redirect()->route('proveedor-marcas.index')
            ->with('success', 'Marca creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = ProveedorMarca::with('proveedor')->findOrFail($id);
        return view('proveedores-marcas.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = ProveedorMarca::findOrFail($id);
        $proveedores = Provider::where('Activo', 1)
            ->orderBy('Proveedor')
            ->get();
        
        return view('proveedores-marcas.edit', compact('item', 'proveedores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = ProveedorMarca::findOrFail($id);
        
        $validated = $request->validate([
            'Id proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Marca' => 'required|string|max:50',
            'Productos' => 'nullable|string|max:255',
            'Vencimiento' => 'nullable|date',
        ]);
        
        $item->update($validated);
        
        return redirect()->route('proveedor-marcas.index')
            ->with('success', 'Marca actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = ProveedorMarca::findOrFail($id);
        $item->delete();
        
        return redirect()->route('proveedor-marcas.index')
            ->with('success', 'Marca eliminada exitosamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = ProveedorMarca::with('proveedor');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->get();
        
        return Excel::download(new ProveedorMarcasExport($items), 'proveedores-marcas.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = ProveedorMarca::with('proveedor');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->get();
        
        $pdf = PDF::loadView('proveedores-marcas.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('proveedores-marcas.pdf');
    }
    
    public function print(Request $request)
    {
        $query = ProveedorMarca::with('proveedor');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->get();
        
        return view('proveedores-marcas.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Proveedores vs Marcas,Id'
        ]);
        
        $ids = $request->ids;
        $count = ProveedorMarca::whereIn('Id', $ids)->delete();
        
        return redirect()->route('proveedor-marcas.index')
            ->with('success', "Se eliminaron $count marcas correctamente.");
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Marca', 'like', "%{$search}%")
                  ->orWhere('Productos', 'like', "%{$search}%")
                  ->orWhereHas('proveedor', function($q2) use ($search) {
                      $q2->where('Proveedor', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('Proveedor')) {
            $query->where('Id proveedor', $request->input('Proveedor'));
        }
        
        if ($request->filled('Marca')) {
            $query->where('Marca', 'like', "%{$request->input('Marca')}%");
        }
        
        if ($request->filled('Productos')) {
            $query->where('Productos', 'like', "%{$request->input('Productos')}%");
        }
        
        if ($request->filled('vencimiento_desde')) {
            $query->where('Vencimiento', '>=', $request->input('vencimiento_desde'));
        }
        
        if ($request->filled('vencimiento_hasta')) {
            $query->where('Vencimiento', '<=', $request->input('vencimiento_hasta'));
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($request->input('sort_by'), $sortDir);
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
        
        if ($request->filled('Proveedor')) {
            $proveedor = Provider::find($request->input('Proveedor'));
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor : 'Desconocido');
        }
        
        if ($request->filled('Marca')) {
            $filtros[] = "Marca: " . $request->Marca;
        }
        
        if ($request->filled('Productos')) {
            $filtros[] = "Productos: " . $request->Productos;
        }
        
        if ($request->filled('vencimiento_desde')) {
            $filtros[] = "Vencimiento desde: " . $request->vencimiento_desde;
        }
        
        if ($request->filled('vencimiento_hasta')) {
            $filtros[] = "Vencimiento hasta: " . $request->vencimiento_hasta;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}