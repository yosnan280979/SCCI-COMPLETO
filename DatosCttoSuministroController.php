<?php

namespace App\Http\Controllers;

use App\Models\DatosCttoSuministro;
use App\Models\ContratoSuministro;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DatosCttoSuministroExport;
use PDF;

class DatosCttoSuministroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DatosCttoSuministro::with(['contratoSuministro'])
            ->orderBy('Id Supsum', 'desc');
        
        // Aplicar filtros
        if ($request->filled('contrato')) {
            $query->whereHas('contratoSuministro', function($q) use ($request) {
                $q->where('Descripcion', 'like', '%' . $request->contrato . '%')
                  ->orWhere('No Ctto Suministro', 'like', '%' . $request->contrato . '%');
            });
        }
        
        if ($request->filled('suplemento')) {
            $query->where('No Suplemento', 'like', '%' . $request->suplemento . '%');
        }
        
        if ($request->filled('fecha_firma')) {
            $query->whereDate('Fecha firma Ctto', $request->fecha_firma);
        }
        
        $items = $query->paginate(25);
        
        return view('datos-ctto-suministro.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener contratos suministro para el select
        $contratosSuministro = ContratoSuministro::orderBy('Descripcion')->get();
        return view('datos-ctto-suministro.create', compact('contratosSuministro'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Cttosuministro' => 'required|exists:Contratos Suministro,Id Cttosuministro',
            'No Suplemento' => 'required|integer',
            'Fecha firma Ctto' => 'required|date',
            'Importe CUC' => 'nullable|numeric',
            'Importe CUP' => 'nullable|numeric',
            'Pendiente Finan CUC' => 'nullable|numeric',
            'Pendiente CUP' => 'nullable|numeric',
            'Forma de Pago' => 'nullable|string|max:50',
        ]);
        
        DatosCttoSuministro::create($validated);
        
        return redirect()->route('datos-ctto-suministro.index')
            ->with('success', 'Datos de contrato suministro creados exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = DatosCttoSuministro::with(['contratoSuministro'])->findOrFail($id);
        return view('datos-ctto-suministro.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = DatosCttoSuministro::findOrFail($id);
        $contratosSuministro = ContratoSuministro::orderBy('Descripcion')->get();
        return view('datos-ctto-suministro.edit', compact('item', 'contratosSuministro'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = DatosCttoSuministro::findOrFail($id);
        
        $validated = $request->validate([
            'Id Cttosuministro' => 'required|exists:Contratos Suministro,Id Cttosuministro',
            'No Suplemento' => 'required|integer',
            'Fecha firma Ctto' => 'required|date',
            'Importe CUC' => 'nullable|numeric',
            'Importe CUP' => 'nullable|numeric',
            'Pendiente Finan CUC' => 'nullable|numeric',
            'Pendiente CUP' => 'nullable|numeric',
            'Forma de Pago' => 'nullable|string|max:50',
        ]);
        
        $item->update($validated);
        
        return redirect()->route('datos-ctto-suministro.index')
            ->with('success', 'Datos de contrato suministro actualizados exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = DatosCttoSuministro::findOrFail($id);
        $item->delete();
        
        return redirect()->route('datos-ctto-suministro.index')
            ->with('success', 'Datos de contrato suministro eliminados exitosamente.');
    }
    
    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = DatosCttoSuministro::with(['contratoSuministro'])
            ->orderBy('Id Supsum', 'desc');
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Supsum', $ids);
            }
        }
        
        $items = $query->get();
        
        return Excel::download(new DatosCttoSuministroExport($items), 'datos-contrato-suministro.xlsx');
    }
    
    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = DatosCttoSuministro::with(['contratoSuministro'])
            ->orderBy('Id Supsum', 'desc');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Supsum', $ids);
            }
        }
        
        $items = $query->get();
        
        $pdf = PDF::loadView('datos-ctto-suministro.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('datos-contrato-suministro.pdf');
    }
    
    /**
     * Print view
     */
    public function print(Request $request)
    {
        $query = DatosCttoSuministro::with(['contratoSuministro'])
            ->orderBy('Id Supsum', 'desc');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Supsum', $ids);
            }
        }
        
        $items = $query->get();
        
        return view('datos-ctto-suministro.print', [
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
            'ids.*' => 'exists:Datos Ctto suministro,Id Supsum'
        ]);
        
        $ids = $request->ids;
        $count = DatosCttoSuministro::whereIn('Id Supsum', $ids)->delete();
        
        return redirect()->route('datos-ctto-suministro.index')
            ->with('success', "Se eliminaron $count registros correctamente.");
    }
    
    /**
     * Aplicar filtros comunes
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('contrato')) {
            $query->whereHas('contratoSuministro', function($q) use ($request) {
                $q->where('Descripcion', 'like', '%' . $request->contrato . '%')
                  ->orWhere('No Ctto Suministro', 'like', '%' . $request->contrato . '%');
            });
        }
        
        if ($request->filled('suplemento')) {
            $query->where('No Suplemento', 'like', '%' . $request->suplemento . '%');
        }
        
        if ($request->filled('fecha_firma')) {
            $query->whereDate('Fecha firma Ctto', $request->fecha_firma);
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('contrato')) {
            $filtros[] = "Contrato: " . $request->contrato;
        }
        
        if ($request->filled('suplemento')) {
            $filtros[] = "Suplemento: " . $request->suplemento;
        }
        
        if ($request->filled('fecha_firma')) {
            $filtros[] = "Fecha Firma: " . $request->fecha_firma;
        }
        
        return $filtros;
    }
}