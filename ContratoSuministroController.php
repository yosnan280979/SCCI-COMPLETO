<?php

namespace App\Http\Controllers;

use App\Models\ContratoSuministro;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContratosSuministroExport;
use PDF;

class ContratoSuministroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ContratoSuministro::with(['cliente'])
            ->orderBy('Id Cttosuministro', 'desc');
        
        // Aplicar filtros
        if ($request->filled('No_Ctto')) {
            $query->where('No Ctto Suministro', 'like', '%' . $request->No_Ctto . '%');
        }
        
        if ($request->filled('Cliente')) {
            $query->whereHas('cliente', function($q) use ($request) {
                $q->where('Cliente', 'like', '%' . $request->Cliente . '%');
            });
        }
        
        if ($request->filled('Descripcion')) {
            $query->where('Descripcion', 'like', '%' . $request->Descripcion . '%');
        }
        
        $items = $query->paginate(25);
        
        return view('contratos-suministro.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Cliente::orderBy('Cliente')->get();
        return view('contratos-suministro.create', compact('clientes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'No Ctto Suministro' => 'required|unique:Contratos Suministro,No Ctto Suministro',
            'Id Cliente' => 'required|exists:Nomenclador Clientes,Id Cliente',
            'Descripcion' => 'nullable|string',
            'Observaciones Ctto Sum' => 'nullable|string',
        ]);
        
        ContratoSuministro::create($validated);
        
        return redirect()->route('contratos-suministro.index')
            ->with('success', 'Contrato de suministro creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $contratoSuministro = ContratoSuministro::with(['cliente'])->findOrFail($id);
        return view('contratos-suministro.show', compact('contratoSuministro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $contratoSuministro = ContratoSuministro::findOrFail($id);
        $clientes = Cliente::orderBy('Cliente')->get();
        return view('contratos-suministro.edit', compact('contratoSuministro', 'clientes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $contratoSuministro = ContratoSuministro::findOrFail($id);
        
        $validated = $request->validate([
            'No Ctto Suministro' => 'required|unique:Contratos Suministro,No Ctto Suministro,' . $contratoSuministro->{'Id Cttosuministro'} . ',Id Cttosuministro',
            'Id Cliente' => 'required|exists:Nomenclador Clientes,Id Cliente',
            'Descripcion' => 'nullable|string',
            'Observaciones Ctto Sum' => 'nullable|string',
        ]);
        
        $contratoSuministro->update($validated);
        
        return redirect()->route('contratos-suministro.index')
            ->with('success', 'Contrato de suministro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $contratoSuministro = ContratoSuministro::findOrFail($id);
        $contratoSuministro->delete();
        
        return redirect()->route('contratos-suministro.index')
            ->with('success', 'Contrato de suministro eliminado exitosamente.');
    }
    
    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = ContratoSuministro::with(['cliente'])
            ->orderBy('Id Cttosuministro', 'desc');
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Cttosuministro', $ids);
            }
        }
        
        $items = $query->get();
        
        return Excel::download(new ContratosSuministroExport($items), 'contratos-suministro.xlsx');
    }
    
    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = ContratoSuministro::with(['cliente'])
            ->orderBy('Id Cttosuministro', 'desc');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Cttosuministro', $ids);
            }
        }
        
        $items = $query->get();
        
        $pdf = PDF::loadView('contratos-suministro.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('contratos-suministro.pdf');
    }
    
    /**
     * Print view
     */
    public function print(Request $request)
    {
        $query = ContratoSuministro::with(['cliente'])
            ->orderBy('Id Cttosuministro', 'desc');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Cttosuministro', $ids);
            }
        }
        
        $items = $query->get();
        
        return view('contratos-suministro.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Destroy multiple items
     */
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Contratos Suministro,Id Cttosuministro'
        ]);
        
        $ids = $request->ids;
        $count = ContratoSuministro::whereIn('Id Cttosuministro', $ids)->delete();
        
        return redirect()->route('contratos-suministro.index')
            ->with('success', "Se eliminaron $count contratos correctamente.");
    }
    
    /**
     * Aplicar filtros comunes
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('No_Ctto')) {
            $query->where('No Ctto Suministro', 'like', '%' . $request->No_Ctto . '%');
        }
        
        if ($request->filled('Cliente')) {
            $query->whereHas('cliente', function($q) use ($request) {
                $q->where('Cliente', 'like', '%' . $request->Cliente . '%');
            });
        }
        
        if ($request->filled('Descripcion')) {
            $query->where('Descripcion', 'like', '%' . $request->Descripcion . '%');
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('No_Ctto')) {
            $filtros[] = "No. Contrato: " . $request->No_Ctto;
        }
        
        if ($request->filled('Cliente')) {
            $filtros[] = "Cliente: " . $request->Cliente;
        }
        
        if ($request->filled('Descripcion')) {
            $filtros[] = "Descripción: " . $request->Descripcion;
        }
        
        return $filtros;
    }
}