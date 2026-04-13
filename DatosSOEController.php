<?php

namespace App\Http\Controllers;

use App\Models\DatosSOE;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DatosSOEExport;
use PDF;

class DatosSOEController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DatosSOE::with(['contrato']);
        
        // Aplicar filtros
        if ($request->filled('no_soe')) {
            $query->where('Id SOE', 'like', "%{$request->no_soe}%");
        }
        
        if ($request->filled('id_ctto')) {
            $query->where('Id Ctto', $request->id_ctto);
        }
        
        if ($request->filled('no_suplemento')) {
            $query->where('No Suplemento', 'like', "%{$request->no_suplemento}%");
        }
        
        if ($request->filled('cancelado_soe') && $request->cancelado_soe !== '') {
            $query->where('Cancelado SOE', $request->cancelado_soe);
        }

        // Filtros de fecha - AGREGADOS
        if ($request->filled('fecha_desde')) {
            $query->where('Fecha Comite', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->where('Fecha Comite', '<=', $request->fecha_hasta);
        }

        // Filtros de valor - AGREGADOS
        if ($request->filled('valor_desde')) {
            $query->where('Valor Ctto CUC', '>=', $request->valor_desde);
        }
        
        if ($request->filled('valor_hasta')) {
            $query->where('Valor Ctto CUC', '<=', $request->valor_hasta);
        }
        
        // Ordenación - CORREGIDO
        $sortBy = $request->input('sort_by', 'Id SOE');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $datosSOE = $query->paginate(25)->appends($request->query());
        
        return view('datos-soe.index', compact('datosSOE'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $contratos = Contrato::orderBy('No Ctto', 'asc')->get();
        return view('datos-soe.create', compact('contratos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Ctto' => 'required|exists:Contratos,Id Ctto',
            'No Suplemento' => 'nullable|integer',
            'Fecha Comite' => 'nullable|date',
            'No Acta' => 'nullable|string|max:50',
            'No Acuerdo' => 'nullable|string|max:50',
            'Valor Mercancia' => 'nullable|numeric',
            'Valor Ctto CUC' => 'nullable|numeric',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
            'Valor Mon Prov' => 'nullable|numeric',
            'No Referencia' => 'nullable|string|max:6',
            'Fecha CAD Gescons' => 'nullable|date',
            'Fecha CAD MICONS' => 'nullable|date',
            'No Acta MICONS' => 'nullable|string|max:20',
            'Fecha firma Ctto' => 'nullable|date',
            'Fecha emision certif' => 'nullable|date',
            'Pendiente finan CUC' => 'nullable|numeric',
            'Pendiente finan CUP' => 'nullable|numeric',
            'Total embarques' => 'nullable|integer',
            'Observaciones Juridico' => 'nullable|string',
            'Observaciones SOE' => 'nullable|string',
            'Observaciones Especialista' => 'nullable|string',
            'Observaciones' => 'nullable|string',
            'Cancelado SOE' => 'nullable|boolean',
            'Forma de Pago' => 'nullable|string|max:255',
            'Anular Valores' => 'nullable|boolean',
            'Id MomentoSOE' => 'nullable|exists:Nomenclador de Momentos SOE,Id MomentoSOE',
            'Año finan' => 'nullable|integer',
            'Id Linea credito' => 'nullable|exists:Nomenclador Líneas Crédito,Id Lineacredito',
        ]);
        
        DatosSOE::create($validated);
        
        return redirect()->route('datos-soe.index')
            ->with('success', 'Datos SOE creados exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $datosSOE = DatosSOE::with(['contrato'])->findOrFail($id);
        return view('datos-soe.show', compact('datosSOE'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $datosSOE = DatosSOE::findOrFail($id);
        $contratos = Contrato::orderBy('No Ctto', 'asc')->get();
        return view('datos-soe.edit', compact('datosSOE', 'contratos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $datosSOE = DatosSOE::findOrFail($id);
        
        $validated = $request->validate([
            'Id Ctto' => 'required|exists:Contratos,Id Ctto',
            'No Suplemento' => 'nullable|integer',
            'Fecha Comite' => 'nullable|date',
            'No Acta' => 'nullable|string|max:50',
            'No Acuerdo' => 'nullable|string|max:50',
            'Valor Mercancia' => 'nullable|numeric',
            'Valor Ctto CUC' => 'nullable|numeric',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
            'Valor Mon Prov' => 'nullable|numeric',
            'No Referencia' => 'nullable|string|max:6',
            'Fecha CAD Gescons' => 'nullable|date',
            'Fecha CAD MICONS' => 'nullable|date',
            'No Acta MICONS' => 'nullable|string|max:20',
            'Fecha firma Ctto' => 'nullable|date',
            'Fecha emision certif' => 'nullable|date',
            'Pendiente finan CUC' => 'nullable|numeric',
            'Pendiente finan CUP' => 'nullable|numeric',
            'Total embarques' => 'nullable|integer',
            'Observaciones Juridico' => 'nullable|string',
            'Observaciones SOE' => 'nullable|string',
            'Observaciones Especialista' => 'nullable|string',
            'Observaciones' => 'nullable|string',
            'Cancelado SOE' => 'nullable|boolean',
            'Forma de Pago' => 'nullable|string|max:255',
            'Anular Valores' => 'nullable|boolean',
            'Id MomentoSOE' => 'nullable|exists:Nomenclador de Momentos SOE,Id MomentoSOE',
            'Año finan' => 'nullable|integer',
            'Id Linea credito' => 'nullable|exists:Nomenclador Líneas Crédito,Id Lineacredito',
        ]);
        
        $datosSOE->update($validated);
        
        return redirect()->route('datos-soe.index')
            ->with('success', 'Datos SOE actualizados exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $datosSOE = DatosSOE::findOrFail($id);
        $datosSOE->delete();
        
        return redirect()->route('datos-soe.index')
            ->with('success', 'Datos SOE eliminados exitosamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = DatosSOE::with(['contrato']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id SOE', $ids);
            }
        }
        
        $datosSOE = $query->get();
        
        return Excel::download(new DatosSOEExport($datosSOE), 'datos-soe.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = DatosSOE::with(['contrato']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id SOE', $ids);
            }
        }
        
        $datosSOE = $query->get();
        
        $pdf = PDF::loadView('datos-soe.pdf', [
            'datosSOE' => $datosSOE,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('datos-soe.pdf');
    }
    
    public function print(Request $request)
    {
        $query = DatosSOE::with(['contrato']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id SOE', $ids);
            }
        }
        
        $datosSOE = $query->get();
        
        return view('datos-soe.print', [
            'datosSOE' => $datosSOE,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Datos SOE,Id SOE'
        ]);
        
        $ids = $request->ids;
        $count = DatosSOE::whereIn('Id SOE', $ids)->delete();
        
        return redirect()->route('datos-soe.index')
            ->with('success', "Se eliminaron $count registros SOE correctamente.");
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('no_soe')) {
            $query->where('Id SOE', 'like', "%{$request->no_soe}%");
        }
        
        if ($request->filled('id_ctto')) {
            $query->where('Id Ctto', $request->id_ctto);
        }
        
        if ($request->filled('no_suplemento')) {
            $query->where('No Suplemento', 'like', "%{$request->no_suplemento}%");
        }
        
        if ($request->filled('cancelado_soe') && $request->cancelado_soe !== '') {
            $query->where('Cancelado SOE', $request->cancelado_soe);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('Fecha Comite', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->where('Fecha Comite', '<=', $request->fecha_hasta);
        }

        if ($request->filled('valor_desde')) {
            $query->where('Valor Ctto CUC', '>=', $request->valor_desde);
        }
        
        if ($request->filled('valor_hasta')) {
            $query->where('Valor Ctto CUC', '<=', $request->valor_hasta);
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($request->input('sort_by'), $sortDir);
        } else {
            $query->orderBy('Id SOE', 'desc');
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('no_soe')) {
            $filtros[] = "No. SOE: " . $request->no_soe;
        }
        
        if ($request->filled('id_ctto')) {
            $contrato = Contrato::find($request->id_ctto);
            $filtros[] = "Contrato: " . ($contrato ? $contrato->{'No Ctto'} : $request->id_ctto);
        }
        
        if ($request->filled('no_suplemento')) {
            $filtros[] = "No. Suplemento: " . $request->no_suplemento;
        }
        
        if ($request->filled('cancelado_soe')) {
            $filtros[] = "Estado: " . ($request->cancelado_soe == '1' ? 'Cancelado' : 'Activo');
        }

        if ($request->filled('fecha_desde')) {
            $filtros[] = "Fecha desde: " . $request->fecha_desde;
        }

        if ($request->filled('fecha_hasta')) {
            $filtros[] = "Fecha hasta: " . $request->fecha_hasta;
        }

        if ($request->filled('valor_desde')) {
            $filtros[] = "Valor desde: " . $request->valor_desde;
        }

        if ($request->filled('valor_hasta')) {
            $filtros[] = "Valor hasta: " . $request->valor_hasta;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}