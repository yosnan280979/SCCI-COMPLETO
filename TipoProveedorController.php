<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoProveedor;
use App\Exports\TipoProveedorExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class TipoProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:lector');
    }
    
    public function index(Request $request)
    {
        $query = TipoProveedor::query();
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('Tipo Proveedor', 'like', "%{$search}%");
        }
        
        // Ordenamiento con validación
        $sort = $request->input('sort', 'Id Tipo Prov');
        $order = $request->input('order', 'asc');
        
        // Validar que order sea asc o desc
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'asc';
        }
        
        // Validar columnas de ordenamiento permitidas
        $allowedSortColumns = ['Id Tipo Prov', 'Tipo Proveedor', 'id', 'tipo'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'Id Tipo Prov';
        }
        
        // Mapear nombres de vista a nombres de columna reales
        $sort = $this->mapSortColumn($sort);
        
        $query->orderBy($sort, $order);
        
        $tipoProveedores = $query->paginate(15);
        
        return view('nomencladores.tipo_proveedor.index', compact('tipoProveedores'));
    }
    
    public function create()
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo-proveedor.index')->with('error', 'No tienes permisos para crear tipos de proveedor.');
        }
        
        return view('nomencladores.tipo_proveedor.create');
    }
    
    public function store(Request $request)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo-proveedor.index')->with('error', 'No tienes permisos para crear tipos de proveedor.');
        }
        
        $request->validate([
            'Tipo Proveedor' => 'required|string|max:20|unique:Nomenclador Tipo Proveedor,Tipo Proveedor',
        ]);
        
        TipoProveedor::create([
            'Tipo Proveedor' => $request->input('Tipo Proveedor')
        ]);
        
        return redirect()->route('nomencladores.tipo-proveedor.index')
            ->with('success', 'Tipo de Proveedor creado exitosamente.');
    }
    
    public function show($id)
    {
        $tipoProveedor = TipoProveedor::findOrFail($id);
        
        return view('nomencladores.tipo_proveedor.show', compact('tipoProveedor'));
    }
    
    public function edit($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo-proveedor.index')->with('error', 'No tienes permisos para editar tipos de proveedor.');
        }
        
        $tipoProveedor = TipoProveedor::findOrFail($id);
        
        return view('nomencladores.tipo_proveedor.edit', compact('tipoProveedor'));
    }
    
    public function update(Request $request, $id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo-proveedor.index')->with('error', 'No tienes permisos para editar tipos de proveedor.');
        }
        
        $tipoProveedor = TipoProveedor::findOrFail($id);
        
        $request->validate([
            'Tipo Proveedor' => 'required|string|max:20|unique:Nomenclador Tipo Proveedor,Tipo Proveedor,' . $tipoProveedor->{'Id Tipo Prov'} . ',Id Tipo Prov',
        ]);
        
        $tipoProveedor->update([
            'Tipo Proveedor' => $request->input('Tipo Proveedor')
        ]);
        
        return redirect()->route('nomencladores.tipo-proveedor.show', $tipoProveedor->{'Id Tipo Prov'})
            ->with('success', 'Tipo de Proveedor actualizado exitosamente.');
    }
    
    public function destroy($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} != 1) {
            return redirect()->route('nomencladores.tipo-proveedor.index')->with('error', 'No tienes permisos para eliminar tipos de proveedor.');
        }
        
        $tipoProveedor = TipoProveedor::findOrFail($id);
        $tipoProveedor->delete();
        
        return redirect()->route('nomencladores.tipo-proveedor.index')
            ->with('success', 'Tipo de Proveedor eliminado exitosamente.');
    }
    
    // Métodos de exportación
    public function exportExcel(Request $request)
    {
        $query = TipoProveedor::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('Tipo Proveedor', 'like', "%{$search}%");
        }
        
        // Ordenamiento con validación
        $sort = $request->input('sort', 'Id Tipo Prov');
        $order = $request->input('order', 'asc');
        
        // Validar que order sea asc o desc
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'asc';
        }
        
        // Validar columnas de ordenamiento permitidas
        $allowedSortColumns = ['Id Tipo Prov', 'Tipo Proveedor', 'id', 'tipo'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'Id Tipo Prov';
        }
        
        // Mapear nombres de vista a nombres de columna reales
        $sort = $this->mapSortColumn($sort);
        
        $query->orderBy($sort, $order);
        
        // Si hay selección de registros
        $selected = $request->input('selected', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Prov', $ids);
            }
        }
        
        $tipoProveedores = $query->get();
        
        return Excel::download(new TipoProveedorExport($tipoProveedores), 'tipo-proveedor.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = TipoProveedor::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('Tipo Proveedor', 'like', "%{$search}%");
        }
        
        // Ordenamiento con validación
        $sort = $request->input('sort', 'Id Tipo Prov');
        $order = $request->input('order', 'asc');
        
        // Validar que order sea asc o desc
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'asc';
        }
        
        // Validar columnas de ordenamiento permitidas
        $allowedSortColumns = ['Id Tipo Prov', 'Tipo Proveedor', 'id', 'tipo'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'Id Tipo Prov';
        }
        
        // Mapear nombres de vista a nombres de columna reales
        $sort = $this->mapSortColumn($sort);
        
        $query->orderBy($sort, $order);
        
        // Si hay selección de registros
        $selected = $request->input('selected', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Prov', $ids);
            }
        }
        
        $tipoProveedores = $query->get();
        
        $pdf = PDF::loadView('nomencladores.tipo_proveedor.pdf', [
            'tipoProveedores' => $tipoProveedores,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('tipo-proveedor.pdf');
    }
    
    public function print(Request $request)
    {
        $query = TipoProveedor::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('Tipo Proveedor', 'like', "%{$search}%");
        }
        
        // Ordenamiento con validación
        $sort = $request->input('sort', 'Id Tipo Prov');
        $order = $request->input('order', 'asc');
        
        // Validar que order sea asc o desc
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'asc';
        }
        
        // Validar columnas de ordenamiento permitidas
        $allowedSortColumns = ['Id Tipo Prov', 'Tipo Proveedor', 'id', 'tipo'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'Id Tipo Prov';
        }
        
        // Mapear nombres de vista a nombres de columna reales
        $sort = $this->mapSortColumn($sort);
        
        $query->orderBy($sort, $order);
        
        // Si hay selección de registros
        $selected = $request->input('selected', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Prov', $ids);
            }
        }
        
        $tipoProveedores = $query->get();
        
        return view('nomencladores.tipo_proveedor.print', [
            'tipoProveedores' => $tipoProveedores,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Mapear nombres de columna de vista a nombres de columna reales
     */
    private function mapSortColumn($sort)
    {
        $mapping = [
            'id' => 'Id Tipo Prov',
            'tipo' => 'Tipo Proveedor',
        ];
        
        return $mapping[$sort] ?? $sort;
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
        
        if ($request->filled('sort')) {
            $sortText = $this->getSortText($request->sort);
            $orderText = $request->order == 'asc' ? 'Ascendente' : 'Descendente';
            $filtros[] = "Ordenado por: $sortText ($orderText)";
        }
        
        return $filtros;
    }
    
    /**
     * Obtener texto descriptivo para la columna de ordenamiento
     */
    private function getSortText($sort)
    {
        $texts = [
            'id' => 'ID',
            'tipo' => 'Tipo Proveedor',
            'Id Tipo Prov' => 'ID',
            'Tipo Proveedor' => 'Tipo Proveedor',
        ];
        
        return $texts[$sort] ?? $sort;
    }
}