<?php

namespace App\Http\Controllers;

use App\Models\LoadType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoadTypesExport;
use PDF;

class LoadTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = LoadType::query();
        
        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('Tipo Carga', 'like', "%{$search}%");
        }
        
        // Ordenación
        $orderBy = $request->get('order_by', 'Id Tipo Carga');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        $loadTypes = $query->paginate(25)->withQueryString();
        
        return view('nomencladores.load_types.index', compact('loadTypes'));
    }

    public function create()
    {
        return view('nomencladores.load_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Tipo Carga' => 'required|string|max:50|unique:Nomenclador Tipo Carga,Tipo Carga'
        ]);

        LoadType::create($request->all());
        return redirect()->route('nomencladores.load-types.index')
            ->with('success', 'Tipo de Carga creado correctamente');
    }

    public function show($id)
    {
        $loadType = LoadType::findOrFail($id);
        return view('nomencladores.load_types.show', compact('loadType'));
    }

    public function edit($id)
    {
        $loadType = LoadType::findOrFail($id);
        return view('nomencladores.load_types.edit', compact('loadType'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Tipo Carga' => 'required|string|max:50|unique:Nomenclador Tipo Carga,Tipo Carga,' . $id . ',Id Tipo Carga'
        ]);

        $loadType = LoadType::findOrFail($id);
        $loadType->update($request->all());

        return redirect()->route('nomencladores.load-types.index')
            ->with('success', 'Tipo de Carga actualizado correctamente');
    }

    public function destroy($id)
    {
        $loadType = LoadType::findOrFail($id);
        $loadType->delete();

        return redirect()->route('nomencladores.load-types.index')
            ->with('success', 'Tipo de Carga eliminado correctamente');
    }
    
    // Eliminar múltiples tipos de carga
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador Tipo Carga,Id Tipo Carga'
        ]);
        
        $count = LoadType::whereIn('Id Tipo Carga', $request->selected_ids)->delete();
        
        return redirect()->route('nomencladores.load-types.index')
            ->with('success', "{$count} tipos de carga eliminados correctamente");
    }
    
    public function exportExcel(Request $request)
    {
        $query = LoadType::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Tipo Carga', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('selected_ids')) {
            $ids = explode(',', $request->selected_ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Carga', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id Tipo Carga', 'asc');
        }
        
        $loadTypes = $query->get();
        
        return Excel::download(new LoadTypesExport($loadTypes), 'tipos_carga_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = LoadType::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Tipo Carga', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('selected_ids')) {
            $ids = explode(',', $request->selected_ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Carga', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id Tipo Carga', 'asc');
        }
        
        $loadTypes = $query->get();
        
        $pdf = PDF::loadView('nomencladores.load_types.pdf', [
            'loadTypes' => $loadTypes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('tipos_carga_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = LoadType::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Tipo Carga', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('selected_ids')) {
            $ids = explode(',', $request->selected_ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Carga', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id Tipo Carga', 'asc');
        }
        
        $loadTypes = $query->get();
        
        return view('nomencladores.load_types.print', [
            'loadTypes' => $loadTypes,
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
        
        if ($request->filled('order_by')) {
            $orden = $request->order_by . ' (' . $request->get('order_direction', 'asc') . ')';
            $filtros[] = "Orden: " . $orden;
        }
        
        return $filtros;
    }
}