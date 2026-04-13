<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Pais;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BancosExport;
use PDF;

class BancoController extends Controller
{
    public function index(Request $request)
    {
        $query = Banco::with('pais');
        
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Banco', 'like', "%{$search}%");
        }
        
        // Filtro por país
        if ($request->filled('id_pais')) {
            $query->where('Id País', $request->id_pais);
        }
        
        // Ordenación - CORREGIDO: usando nombres consistentes
        $orderBy = $request->input('sort_by', 'Id Banco');
        $orderDirection = $request->input('sort_dir', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $bancos = $query->paginate(25)->appends($request->query());
        $paises = Pais::orderBy('País')->get();
        
        return view('nomencladores.banco.index', compact('bancos', 'paises'));
    }

    public function create()
    {
        $paises = Pais::orderBy('País')->get();
        return view('nomencladores.banco.create', compact('paises'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Banco' => 'required|string|max:50|unique:Nomenclador de Bancos,Banco',
            'Id País' => 'nullable|exists:Nomenclador Paises,Id País'
        ]);

        Banco::create($request->all());
        return redirect()->route('nomencladores.bancos.index')
            ->with('success', 'Banco creado correctamente');
    }

    public function show($id)
    {
        $banco = Banco::with('pais')->findOrFail($id);
        return view('nomencladores.banco.show', compact('banco'));
    }

    public function edit($id)
    {
        $banco = Banco::findOrFail($id);
        $paises = Pais::orderBy('País')->get();
        return view('nomencladores.banco.edit', compact('banco', 'paises'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Banco' => 'required|string|max:50|unique:Nomenclador de Bancos,Banco,' . $id . ',Id Banco',
            'Id País' => 'nullable|exists:Nomenclador Paises,Id País'
        ]);

        $banco = Banco::findOrFail($id);
        $banco->update($request->all());

        return redirect()->route('nomencladores.bancos.index')
            ->with('success', 'Banco actualizado correctamente');
    }

    public function destroy($id)
    {
        $banco = Banco::findOrFail($id);
        $banco->delete();

        return redirect()->route('nomencladores.bancos.index')
            ->with('success', 'Banco eliminado correctamente');
    }
    
    // Eliminar múltiples bancos - CORREGIDO: usando 'ids' en lugar de 'selected_ids'
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Nomenclador de Bancos,Id Banco'
        ]);
        
        $count = Banco::whereIn('Id Banco', $request->ids)->delete();
        
        return redirect()->route('nomencladores.bancos.index')
            ->with('success', "{$count} bancos eliminados correctamente");
    }
    
    public function exportExcel(Request $request)
    {
        $query = Banco::with('pais');
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Banco', 'like', "%{$search}%");
        }
        
        if ($request->filled('id_pais')) {
            $query->where('Id País', $request->id_pais);
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('selected')) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Banco', $ids);
            }
        }
        
        // Ordenación - CORREGIDO
        $orderBy = $request->input('order_by', 'Id Banco');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        $bancos = $query->get();
        
        return Excel::download(new BancosExport($bancos), 'bancos_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Banco::with('pais');
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Banco', 'like', "%{$search}%");
        }
        
        if ($request->filled('id_pais')) {
            $query->where('Id País', $request->id_pais);
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('selected')) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Banco', $ids);
            }
        }
        
        // Ordenación - CORREGIDO
        $orderBy = $request->input('order_by', 'Id Banco');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        $bancos = $query->get();
        
        $pdf = PDF::loadView('nomencladores.banco.pdf', ['bancos' => $bancos]);
        return $pdf->download('bancos_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Banco::with('pais');
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Banco', 'like', "%{$search}%");
        }
        
        if ($request->filled('id_pais')) {
            $query->where('Id País', $request->id_pais);
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('selected')) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Banco', $ids);
            }
        }
        
        // Ordenación - CORREGIDO
        $orderBy = $request->input('order_by', 'Id Banco');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        $bancos = $query->get();
        
        return view('nomencladores.banco.print', compact('bancos'));
    }
}