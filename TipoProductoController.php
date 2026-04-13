<?php

namespace App\Http\Controllers;

use App\Models\TipoProducto;
use App\Models\TipoProductoGeneral;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TiposProductoExport;
use PDF;

class TipoProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoProducto::with('tipoProductoGeneral');
        
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Producto', 'like', "%{$search}%")
                  ->orWhere('Siglas', 'like', "%{$search}%");
            });
        }
        
        // Filtro por tipo de producto general
        if ($request->filled('id_tipo_prodg')) {
            $query->where('Id tipo prodg', $request->id_tipo_prodg);
        }
        
        // Ordenación - CON VALIDACIÓN MEJORADA
        $sortBy = $request->input('sort_by', 'Id Tipo Producto');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar y sanitizar sort_by
        $validSortColumns = ['Id Tipo Producto', 'Tipo Producto', 'Siglas', 'Id tipo prodg'];
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Id Tipo Producto';
        }
        
        // Validar sort_dir
        $sortDir = strtolower($sortDir);
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $tiposProducto = $query->paginate(20)->appends($request->query());
        $tiposProductoGeneral = TipoProductoGeneral::all();
        
        return view('nomencladores.tipos_productos.index', compact('tiposProducto', 'tiposProductoGeneral'));
    }

    // ... otros métodos (create, store, show, edit, update, destroy) ...

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron tipos de producto para eliminar.');
        }
        
        $count = TipoProducto::whereIn('Id Tipo Producto', $ids)->delete();
        
        return redirect()->route('nomencladores.tipos_productos.index')
            ->with('success', "Se eliminaron $count tipos de producto correctamente.");
    }
    
    public function exportExcel(Request $request)
    {
        $query = TipoProducto::with('tipoProductoGeneral');
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Producto', 'like', "%{$search}%")
                  ->orWhere('Siglas', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_tipo_prodg')) {
            $query->where('Id tipo prodg', $request->id_tipo_prodg);
        }
        
        // Filtrar por IDs seleccionados
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Producto', $ids);
            }
        }
        
        // Ordenación - CON VALIDACIÓN MEJORADA
        $sortBy = $request->input('sort_by', 'Id Tipo Producto');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar y sanitizar sort_by
        $validSortColumns = ['Id Tipo Producto', 'Tipo Producto', 'Siglas', 'Id tipo prodg'];
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Id Tipo Producto';
        }
        
        // Validar sort_dir
        $sortDir = strtolower($sortDir);
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $tiposProducto = $query->get();
        
        return Excel::download(new TiposProductoExport($tiposProducto), 'tipos_producto.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = TipoProducto::with('tipoProductoGeneral');
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Producto', 'like', "%{$search}%")
                  ->orWhere('Siglas', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_tipo_prodg')) {
            $query->where('Id tipo prodg', $request->id_tipo_prodg);
        }
        
        // Filtrar por IDs seleccionados
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Producto', $ids);
            }
        }
        
        // Ordenación - CON VALIDACIÓN MEJORADA
        $sortBy = $request->input('sort_by', 'Id Tipo Producto');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar y sanitizar sort_by
        $validSortColumns = ['Id Tipo Producto', 'Tipo Producto', 'Siglas', 'Id tipo prodg'];
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Id Tipo Producto';
        }
        
        // Validar sort_dir
        $sortDir = strtolower($sortDir);
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $tiposProducto = $query->get();
        
        $pdf = PDF::loadView('nomencladores.tipos_productos.pdf', [
            'tiposProducto' => $tiposProducto,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('tipos_producto.pdf');
    }
    
    public function print(Request $request)
    {
        $query = TipoProducto::with('tipoProductoGeneral');
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Producto', 'like', "%{$search}%")
                  ->orWhere('Siglas', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_tipo_prodg')) {
            $query->where('Id tipo prodg', $request->id_tipo_prodg);
        }
        
        // Filtrar por IDs seleccionados
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Producto', $ids);
            }
        }
        
        // Ordenación - CON VALIDACIÓN MEJORADA
        $sortBy = $request->input('sort_by', 'Id Tipo Producto');
        $sortDir = $request->input('sort_dir', 'asc');
        
        // Validar y sanitizar sort_by
        $validSortColumns = ['Id Tipo Producto', 'Tipo Producto', 'Siglas', 'Id tipo prodg'];
        if (empty($sortBy) || !in_array($sortBy, $validSortColumns)) {
            $sortBy = 'Id Tipo Producto';
        }
        
        // Validar sort_dir
        $sortDir = strtolower($sortDir);
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy($sortBy, $sortDir);
        
        $tiposProducto = $query->get();
        
        return view('nomencladores.tipos_productos.print', [
            'tiposProducto' => $tiposProducto,
            'filtros' => $this->obtenerFiltros($request)
        ]);
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
        
        if ($request->filled('id_tipo_prodg')) {
            $tipoGeneral = TipoProductoGeneral::find($request->id_tipo_prodg);
            $filtros[] = "Tipo Producto General: " . ($tipoGeneral ? $tipoGeneral->{'Tipo Prod general'} : 'Desconocido');
        }
        
        return $filtros;
    }
}