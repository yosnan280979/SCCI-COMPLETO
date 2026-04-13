<?php

namespace App\Http\Controllers;

use App\Models\Capacidad;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CapacidadesExport;
use PDF;

class CapacidadController extends Controller
{
    public function index(Request $request)
    {
        $query = Capacidad::query();
        
        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Capacidad', 'like', "%{$search}%")
                  ->orWhere('Descripcion', 'like', "%{$search}%");
            });
        }
        
        // Ordenación
        $orderBy = $request->get('order_by', 'Id Capacidad');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        $capacidades = $query->paginate(25)->withQueryString();
        
        return view('nomencladores.capacities.index', compact('capacidades'));
    }

    public function create()
    {
        return view('nomencladores.capacities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Capacidad' => 'required|string|max:3|unique:Nomenclador Capacidades,Capacidad',
            'Descripcion' => 'required|string|max:50'
        ]);

        Capacidad::create($request->all());
        return redirect()->route('nomencladores.capacities.index')
            ->with('success', 'Capacidad creada correctamente');
    }

    public function show($id)
    {
        $capacidad = Capacidad::findOrFail($id);
        return view('nomencladores.capacities.show', compact('capacidad'));
    }

    public function edit($id)
    {
        $capacidad = Capacidad::findOrFail($id);
        return view('nomencladores.capacities.edit', compact('capacidad'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Capacidad' => 'required|string|max:3|unique:Nomenclador Capacidades,Capacidad,' . $id . ',Id Capacidad',
            'Descripcion' => 'required|string|max:50'
        ]);

        $capacidad = Capacidad::findOrFail($id);
        $capacidad->update($request->all());

        return redirect()->route('nomencladores.capacities.index')
            ->with('success', 'Capacidad actualizada correctamente');
    }

    public function destroy($id)
    {
        $capacidad = Capacidad::findOrFail($id);
        $capacidad->delete();

        return redirect()->route('nomencladores.capacities.index')
            ->with('success', 'Capacidad eliminada correctamente');
    }
    
    // Eliminar múltiples capacidades
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador Capacidades,Id Capacidad'
        ]);
        
        $count = Capacidad::whereIn('Id Capacidad', $request->selected_ids)->delete();
        
        return redirect()->route('nomencladores.capacities.index')
            ->with('success', "{$count} capacidades eliminadas correctamente");
    }
    
    public function exportExcel(Request $request)
    {
        $query = Capacidad::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Capacidad', 'like', "%{$search}%")
                  ->orWhere('Descripcion', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('ids') && !empty($request->ids)) {
            $ids = explode(',', $request->ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Capacidad', $ids);
            }
        }
        
        // Ordenación - SOLUCIÓN AL ERROR
        $orderBy = $request->get('order_by', 'Id Capacidad');
        $orderDirection = $request->get('order_direction', 'asc');
        
        // Validar que orderDirection sea válido
        if (!in_array(strtolower($orderDirection), ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        // Solo ordenar si se especificó un campo de ordenación
        if (!empty($orderBy)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('Id Capacidad', 'asc');
        }
        
        $capacidades = $query->get();
        
        return Excel::download(new CapacidadesExport($capacidades), 'capacidades_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Capacidad::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Capacidad', 'like', "%{$search}%")
                  ->orWhere('Descripcion', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('ids') && !empty($request->ids)) {
            $ids = explode(',', $request->ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Capacidad', $ids);
            }
        }
        
        // Ordenación - SOLUCIÓN AL ERROR
        $orderBy = $request->get('order_by', 'Id Capacidad');
        $orderDirection = $request->get('order_direction', 'asc');
        
        // Validar que orderDirection sea válido
        if (!in_array(strtolower($orderDirection), ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        // Solo ordenar si se especificó un campo de ordenación
        if (!empty($orderBy)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('Id Capacidad', 'asc');
        }
        
        $capacidades = $query->get();
        
        $pdf = PDF::loadView('nomencladores.capacities.pdf', ['capacidades' => $capacidades]);
        return $pdf->download('capacidades_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Capacidad::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Capacidad', 'like', "%{$search}%")
                  ->orWhere('Descripcion', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('ids') && !empty($request->ids)) {
            $ids = explode(',', $request->ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Capacidad', $ids);
            }
        }
        
        // Ordenación - SOLUCIÓN AL ERROR
        $orderBy = $request->get('order_by', 'Id Capacidad');
        $orderDirection = $request->get('order_direction', 'asc');
        
        // Validar que orderDirection sea válido
        if (!in_array(strtolower($orderDirection), ['asc', 'desc'])) {
            $orderDirection = 'asc';
        }
        
        // Solo ordenar si se especificó un campo de ordenación
        if (!empty($orderBy)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('Id Capacidad', 'asc');
        }
        
        $capacidades = $query->get();
        
        return view('nomencladores.capacities.print', compact('capacidades'));
    }
}