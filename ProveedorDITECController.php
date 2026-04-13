<?php

namespace App\Http\Controllers;

use App\Models\ProveedorDITEC;
use App\Models\DITEC;
use App\Models\Provider;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorDITECExport;
use PDF;

class ProveedorDITECController extends Controller
{
    public function index(Request $request)
    {
        $query = ProveedorDITEC::with(['ditec', 'proveedor']);
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('ditec', function($q2) use ($search) {
                    $q2->where('No DITEC', 'like', "%{$search}%")
                       ->orWhere('Producto', 'like', "%{$search}%")
                       ->orWhere('Fabricante', 'like', "%{$search}%");
                })->orWhereHas('proveedor', function($q2) use ($search) {
                    $q2->where('Proveedor', 'like', "%{$search}%")
                       ->orWhere('Correo', 'like', "%{$search}%")
                       ->orWhere('Codigo MINCEX', 'like', "%{$search}%");
                });
            });
        }
        
        if ($request->filled('Id_DITEC')) {
            $query->where('Id DITEC', $request->input('Id_DITEC'));
        }
        
        if ($request->filled('Id_Proveedor')) {
            $query->where('Id Proveedor', $request->input('Id_Proveedor'));
        }
        
        if ($request->filled('Pais')) {
            $pais = $request->input('Pais');
            $query->whereHas('proveedor', function($q) use ($pais) {
                $q->where('País', $pais);
            });
        }
        
        if ($request->filled('activo')) {
            $activo = $request->input('activo');
            $query->whereHas('proveedor', function($q) use ($activo) {
                $q->where('Activo', $activo);
            });
        }
        
        if ($request->filled('id_tipo_prov')) {
            $tipo = $request->input('id_tipo_prov');
            $query->whereHas('proveedor', function($q) use ($tipo) {
                $q->where('Id Tipo Prov', $tipo);
            });
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        
        // Manejar ordenamiento por campos de relaciones
        if (in_array($sortBy, ['No DITEC', 'Producto', 'Fabricante'])) {
            $query->join('DITEC', 'Proveedor vs DITEC.Id DITEC', '=', 'DITEC.Id Ditec');
            $column = match($sortBy) {
                'No DITEC' => 'DITEC.No DITEC',
                'Producto' => 'DITEC.Producto',
                'Fabricante' => 'DITEC.Fabricante',
                default => 'DITEC.Id Ditec'
            };
            $query->orderBy($column, $sortDir);
        } elseif (in_array($sortBy, ['Proveedor', 'País', 'Activo', 'Id Tipo Prov'])) {
            $query->join('Nomenclador Proveedores', 'Proveedor vs DITEC.Id Proveedor', '=', 'Nomenclador Proveedores.Id Proveedor');
            $column = match($sortBy) {
                'Proveedor' => 'Nomenclador Proveedores.Proveedor',
                'País' => 'Nomenclador Proveedores.País',
                'Activo' => 'Nomenclador Proveedores.Activo',
                'Id Tipo Prov' => 'Nomenclador Proveedores.Id Tipo Prov',
                default => 'Nomenclador Proveedores.Id Proveedor'
            };
            $query->orderBy($column, $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
        
        // Paginación
        $items = $query->paginate(20)->appends($request->query());
        
        // Obtener datos para filtros
        $ditecs = DITEC::orderBy('No DITEC')->get();
        $proveedores = Provider::orderBy('Proveedor')->get();
        
        // Obtener lista de países únicos
        $paises = Provider::whereNotNull('País')
                         ->distinct()
                         ->pluck('País')
                         ->sort()
                         ->values()
                         ->toArray();
        
        // Obtener tipos de proveedor únicos (basados en Id Tipo Prov)
        $tiposProveedor = Provider::whereNotNull('Id Tipo Prov')
                                 ->distinct()
                                 ->pluck('Id Tipo Prov')
                                 ->sort()
                                 ->values()
                                 ->toArray();
        
        return view('proveedor-ditec.index', compact('items', 'ditecs', 'proveedores', 'paises', 'tiposProveedor'));
    }

    public function create()
    {
        $ditecs = DITEC::orderBy('No DITEC')->get();
        $proveedores = Provider::orderBy('Proveedor')->get();
        
        return view('proveedor-ditec.create', compact('ditecs', 'proveedores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id DITEC' => 'required|exists:DITEC,Id Ditec',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
        ]);
        
        ProveedorDITEC::create($validated);
        
        return redirect()->route('proveedor-ditec.index')
            ->with('success', 'Relación creada exitosamente.');
    }

    public function show($id)
    {
        $item = ProveedorDITEC::with(['ditec', 'proveedor'])->findOrFail($id);
        return view('proveedor-ditec.show', compact('item'));
    }

    public function edit($id)
    {
        $item = ProveedorDITEC::findOrFail($id);
        $ditecs = DITEC::orderBy('No DITEC')->get();
        $proveedores = Provider::orderBy('Proveedor')->get();
        
        return view('proveedor-ditec.edit', compact('item', 'ditecs', 'proveedores'));
    }

    public function update(Request $request, $id)
    {
        $item = ProveedorDITEC::findOrFail($id);
        
        $validated = $request->validate([
            'Id DITEC' => 'required|exists:DITEC,Id Ditec',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
        ]);
        
        $item->update($validated);
        
        return redirect()->route('proveedor-ditec.index')
            ->with('success', 'Relación actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $item = ProveedorDITEC::findOrFail($id);
        $item->delete();
        
        return redirect()->route('proveedor-ditec.index')
            ->with('success', 'Relación eliminada exitosamente.');
    }
    
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron relaciones para eliminar.');
        }
        
        $count = ProveedorDITEC::whereIn('Id', $ids)->delete();
        
        return redirect()->route('proveedor-ditec.index')
            ->with('success', "Se eliminaron $count relaciones correctamente.");
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = ProveedorDITEC::with(['ditec', 'proveedor']);
        
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
        
        return Excel::download(new ProveedorDITECExport($items), 'proveedor-ditec.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = ProveedorDITEC::with(['ditec', 'proveedor']);
        
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
        
        $pdf = PDF::loadView('proveedor-ditec.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('proveedor-ditec.pdf');
    }
    
    public function print(Request $request)
    {
        $query = ProveedorDITEC::with(['ditec', 'proveedor']);
        
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
        
        return view('proveedor-ditec.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('ditec', function($q2) use ($search) {
                    $q2->where('No DITEC', 'like', "%{$search}%")
                       ->orWhere('Producto', 'like', "%{$search}%")
                       ->orWhere('Fabricante', 'like', "%{$search}%");
                })->orWhereHas('proveedor', function($q2) use ($search) {
                    $q2->where('Proveedor', 'like', "%{$search}%")
                       ->orWhere('Correo', 'like', "%{$search}%")
                       ->orWhere('Codigo MINCEX', 'like', "%{$search}%");
                });
            });
        }
        
        if ($request->filled('Id_DITEC')) {
            $query->where('Id DITEC', $request->input('Id_DITEC'));
        }
        
        if ($request->filled('Id_Proveedor')) {
            $query->where('Id Proveedor', $request->input('Id_Proveedor'));
        }
        
        if ($request->filled('Pais')) {
            $pais = $request->input('Pais');
            $query->whereHas('proveedor', function($q) use ($pais) {
                $q->where('País', $pais);
            });
        }
        
        if ($request->filled('activo')) {
            $activo = $request->input('activo');
            $query->whereHas('proveedor', function($q) use ($activo) {
                $q->where('Activo', $activo);
            });
        }
        
        if ($request->filled('id_tipo_prov')) {
            $tipo = $request->input('id_tipo_prov');
            $query->whereHas('proveedor', function($q) use ($tipo) {
                $q->where('Id Tipo Prov', $tipo);
            });
        }
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('Id_DITEC')) {
            $ditec = DITEC::find($request->input('Id_DITEC'));
            $filtros[] = "DITEC: " . ($ditec ? ($ditec->{'No DITEC'} . ' - ' . $ditec->Producto) : 'Desconocido');
        }
        
        if ($request->filled('Id_Proveedor')) {
            $proveedor = Provider::find($request->input('Id_Proveedor'));
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor . ' (ID: ' . $request->input('Id_Proveedor') . ')' : 'Desconocido');
        }
        
        if ($request->filled('Pais')) {
            $filtros[] = "País: " . $request->input('Pais');
        }
        
        if ($request->filled('activo')) {
            $filtros[] = "Activo: " . ($request->input('activo') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('id_tipo_prov')) {
            $filtros[] = "ID Tipo Proveedor: " . $request->input('id_tipo_prov');
        }
        
        return $filtros;
    }
}