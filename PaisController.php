<?php

namespace App\Http\Controllers;

use App\Models\Pais;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaisesExport;
use PDF;

class PaisController extends Controller
{
    public function index(Request $request)
    {
        $query = Pais::query();
        
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('País', 'like', "%{$search}%");
        }
        
        // Ordenación - CORREGIDO: usando los mismos nombres de parámetros que la vista
        $orderBy = $request->input('order_by', 'Id País');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $paises = $query->paginate(25)->appends($request->query());
        
        return view('nomencladores.countries.index', compact('paises'));
    }

    public function create()
    {
        return view('nomencladores.countries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Id País' => 'required|integer|unique:Nomenclador Paises,Id País',
            'País' => 'required|string|max:50|unique:Nomenclador Paises,País'
        ]);

        Pais::create($request->all());

        return redirect()->route('nomencladores.paises.index')
            ->with('success', 'País creado correctamente.');
    }

    public function show($id)
    {
        $pais = Pais::findOrFail($id);
        return view('nomencladores.countries.show', compact('pais'));
    }

    public function edit($id)
    {
        $pais = Pais::findOrFail($id);
        return view('nomencladores.countries.edit', compact('pais'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'País' => 'required|string|max:50|unique:Nomenclador Paises,País,' . $id . ',Id País'
        ]);

        $pais = Pais::findOrFail($id);
        $pais->update($request->all());

        return redirect()->route('nomencladores.paises.index')
            ->with('success', 'País actualizado correctamente.');
    }

    public function destroy($id)
    {
        $pais = Pais::findOrFail($id);
        $pais->delete();

        return redirect()->route('nomencladores.paises.index')
            ->with('success', 'País eliminado correctamente.');
    }
    
    // Eliminar múltiples países - CORREGIDO: usando 'ids' como parámetro
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Nomenclador Paises,Id País'
        ]);
        
        $count = Pais::whereIn('Id País', $request->ids)->delete();
        
        return redirect()->route('nomencladores.paises.index')
            ->with('success', "{$count} países eliminados correctamente");
    }
    
    // Métodos de exportación - CAMBIADOS A GET (sin @csrf)
    public function exportExcel(Request $request)
    {
        $query = Pais::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('País', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados - CORREGIDO: usando 'selected' que es el nombre en la vista
        if ($request->filled('selected')) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id País', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id País', 'asc');
        }
        
        $paises = $query->get();
        
        return Excel::download(new PaisesExport($paises), 'paises_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Pais::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('País', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados - CORREGIDO: usando 'selected'
        if ($request->filled('selected')) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id País', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id País', 'asc');
        }
        
        $paises = $query->get();
        
        $pdf = PDF::loadView('nomencladores.countries.pdf', ['paises' => $paises]);
        return $pdf->download('paises_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Pais::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('País', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados - CORREGIDO: usando 'selected'
        if ($request->filled('selected')) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id País', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id País', 'asc');
        }
        
        $paises = $query->get();
        
        return view('nomencladores.countries.print', compact('paises'));
    }
}