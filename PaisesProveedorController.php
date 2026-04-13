<?php

namespace App\Http\Controllers;

use App\Models\PaisesProveedor;
use App\Models\Provider;
use App\Models\Pais;
use Illuminate\Http\Request;
use App\Exports\PaisesProveedorExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class PaisesProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = PaisesProveedor::with(['proveedor', 'pais']);
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('proveedor', function($q2) use ($search) {
                    $q2->where('Proveedor', 'like', '%' . $search . '%');
                })->orWhereHas('pais', function($q2) use ($search) {
                    $q2->where('País', 'like', '%' . $search . '%');
                });
            });
        }
        
        if ($request->filled('Proveedor')) {
            $query->whereHas('proveedor', function($q) use ($request) {
                $q->where('Proveedor', 'like', '%' . $request->Proveedor . '%');
            });
        }
        
        if ($request->filled('País')) {
            $query->whereHas('pais', function($q) use ($request) {
                $q->where('País', 'like', '%' . $request->País . '%');
            });
        }
        
        if ($request->filled('Oficina') && $request->Oficina !== '') {
            $query->where('Oficina', $request->Oficina);
        }
        
        // Ordenación - CORREGIDO
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $items = $query->paginate(20)->appends($request->query());
        
        return view('paises-proveedores.index', compact('items'));
    }

    public function create()
    {
        $proveedores = Provider::all();
        $paises = Pais::all();
        return view('paises-proveedores.create', compact('proveedores', 'paises'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_proveedor' => 'nullable|exists:Nomenclador Proveedores,Id Proveedor',
            'Id_Pais' => 'nullable|exists:Nomenclador Paises,Id País',
            'Oficina' => 'required|boolean',
        ]);

        PaisesProveedor::create($validated);
        
        return redirect()->route('paises-proveedor.index')
            ->with('success', 'Relación Países-Proveedores creada exitosamente.');
    }

    public function show($id)
    {
        $item = PaisesProveedor::with(['proveedor', 'pais'])->findOrFail($id);
        return view('paises-proveedores.show', compact('item'));
    }

    public function edit($id)
    {
        $item = PaisesProveedor::findOrFail($id);
        $proveedores = Provider::all();
        $paises = Pais::all();
        return view('paises-proveedores.edit', compact('item', 'proveedores', 'paises'));
    }

    public function update(Request $request, $id)
    {
        $item = PaisesProveedor::findOrFail($id);
        
        $validated = $request->validate([
            'Id_proveedor' => 'nullable|exists:Nomenclador Proveedores,Id Proveedor',
            'Id_Pais' => 'nullable|exists:Nomenclador Paises,Id País',
            'Oficina' => 'required|boolean',
        ]);

        $item->update($validated);
        
        return redirect()->route('paises-proveedor.index')
            ->with('success', 'Relación Países-Proveedores actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $item = PaisesProveedor::findOrFail($id);
        $item->delete();
        
        return redirect()->route('paises-proveedor.index')
            ->with('success', 'Relación Países-Proveedores eliminada exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Paises vs Proveedores,Id'
        ]);
        
        $ids = $request->ids;
        $count = PaisesProveedor::whereIn('Id', $ids)->delete();
        
        return redirect()->route('paises-proveedor.index')
            ->with('success', "Se eliminaron $count registros correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = PaisesProveedor::with(['proveedor', 'pais']);
        
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
        
        $items = $query->get();
        
        return Excel::download(new PaisesProveedorExport($items), 'paises-proveedor.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = PaisesProveedor::with(['proveedor', 'pais']);
        
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
        
        $pdf = PDF::loadView('paises-proveedores.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('paises-proveedor.pdf');
    }

    public function print(Request $request)
    {
        $query = PaisesProveedor::with(['proveedor', 'pais']);
        
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
        
        return view('paises-proveedores.print', [
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
                $q->whereHas('proveedor', function($q2) use ($search) {
                    $q2->where('Proveedor', 'like', '%' . $search . '%');
                })->orWhereHas('pais', function($q2) use ($search) {
                    $q2->where('País', 'like', '%' . $search . '%');
                });
            });
        }
        
        if ($request->filled('Proveedor')) {
            $query->whereHas('proveedor', function($q) use ($request) {
                $q->where('Proveedor', 'like', '%' . $request->Proveedor . '%');
            });
        }
        
        if ($request->filled('País')) {
            $query->whereHas('pais', function($q) use ($request) {
                $q->where('País', 'like', '%' . $request->País . '%');
            });
        }
        
        if ($request->filled('Oficina') && $request->Oficina !== '') {
            $query->where('Oficina', $request->Oficina);
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
            $filtros[] = "Proveedor: " . $request->Proveedor;
        }
        
        if ($request->filled('País')) {
            $filtros[] = "País: " . $request->País;
        }
        
        if ($request->filled('Oficina')) {
            $filtros[] = "Oficina: " . ($request->Oficina == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}