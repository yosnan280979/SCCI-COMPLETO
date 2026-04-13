<?php

namespace App\Http\Controllers;

use App\Models\TipoCttoCubano;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TipoCttoCubanoExport;
use PDF;

class TipoCttoCubanoController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoCttoCubano::query();
        
        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('Tipo Ctto', 'like', "%{$search}%");
        }
        
        // Ordenación
        $orderBy = $request->get('order_by', 'Id Tipo Ctto');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);
        
        $tipos = $query->paginate(25)->withQueryString();
        
        return view('nomencladores.tipo-ctto-cubano.index', compact('tipos'));
    }

    public function create()
    {
        return view('nomencladores.tipo-ctto-cubano.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Tipo Ctto' => 'required|string|max:50|unique:Nomenclador Tipo Ctto Cubano,Tipo Ctto'
        ]);

        TipoCttoCubano::create($request->all());
        
        return redirect()->route('nomencladores.tipo-ctto-cubano.index')
            ->with('success', 'Tipo de Contrato Cubano creado correctamente');
    }

    public function show($id)
    {
        $tipo = TipoCttoCubano::findOrFail($id);
        return view('nomencladores.tipo-ctto-cubano.show', compact('tipo'));
    }

    public function edit($id)
    {
        $tipo = TipoCttoCubano::findOrFail($id);
        return view('nomencladores.tipo-ctto-cubano.edit', compact('tipo'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Tipo Ctto' => 'required|string|max:50|unique:Nomenclador Tipo Ctto Cubano,Tipo Ctto,' . $id . ',Id Tipo Ctto'
        ]);

        $tipo = TipoCttoCubano::findOrFail($id);
        $tipo->update($request->all());

        return redirect()->route('nomencladores.tipo-ctto-cubano.index')
            ->with('success', 'Tipo de Contrato Cubano actualizado correctamente');
    }

    public function destroy($id)
    {
        $tipo = TipoCttoCubano::findOrFail($id);
        $tipo->delete();

        return redirect()->route('nomencladores.tipo-ctto-cubano.index')
            ->with('success', 'Tipo de Contrato Cubano eliminado correctamente');
    }
    
    // Eliminar múltiples tipos
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador Tipo Ctto Cubano,Id Tipo Ctto'
        ]);
        
        $count = TipoCttoCubano::whereIn('Id Tipo Ctto', $request->selected_ids)->delete();
        
        return redirect()->route('nomencladores.tipo-ctto-cubano.index')
            ->with('success', "{$count} tipos de contrato cubano eliminados correctamente");
    }
    
    public function exportExcel(Request $request)
    {
        $query = TipoCttoCubano::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Tipo Ctto', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Ctto', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id Tipo Ctto', 'asc');
        }
        
        $tipos = $query->get();
        
        return Excel::download(new TipoCttoCubanoExport($tipos), 'tipos_contrato_cubano_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = TipoCttoCubano::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Tipo Ctto', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Ctto', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id Tipo Ctto', 'asc');
        }
        
        $tipos = $query->get();
        
        $pdf = PDF::loadView('nomencladores.tipo-ctto-cubano.pdf', [
            'tipos' => $tipos,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('tipos_contrato_cubano_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = TipoCttoCubano::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Tipo Ctto', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo Ctto', $ids);
            }
        }
        
        // Ordenación
        if ($request->filled('order_by')) {
            $query->orderBy($request->order_by, $request->get('order_direction', 'asc'));
        } else {
            $query->orderBy('Id Tipo Ctto', 'asc');
        }
        
        $tipos = $query->get();
        
        return view('nomencladores.tipo-ctto-cubano.print', [
            'tipos' => $tipos,
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
        
        return $filtros;
    }
}