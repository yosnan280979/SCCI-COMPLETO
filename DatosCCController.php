<?php

namespace App\Http\Controllers;

use App\Models\DatosCC;
use App\Models\DatosSOE;
use App\Models\Capacidad;
use App\Models\MomentoCC;
use App\Models\CreditLine;
use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DatosCCExport;
use PDF;

class DatosCCController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DatosCC::with(['moneda', 'capacidad', 'momentoCC', 'lineaCredito']);
        
        // Aplicar filtros
        if ($request->filled('No_CC')) {
            $query->where('No CC', 'like', '%' . $request->input('No_CC') . '%');
        }
        
        if ($request->filled('Id_SOE')) {
            $query->where('Id SOE', $request->input('Id_SOE'));
        }
        
        if ($request->filled('Id_Capacidad')) {
            $query->where('Id Capacidad', $request->input('Id_Capacidad'));
        }
        
        if ($request->filled('Id_MomentoCC')) {
            $query->where('Id MomentoCC', $request->input('Id_MomentoCC'));
        }
        
        if ($request->filled('Id_Linea_credito')) {
            $query->where('Id Linea credito', $request->input('Id_Linea_credito'));
        }
        
        if ($request->filled('Año_finan')) {
            $query->where('Año finan', $request->input('Año_finan'));
        }
        
        // Filtros de fecha - CORREGIDOS
        if ($request->filled('fecha_inicio')) {
            $query->where('Fecha Presentada CC', '>=', $request->input('fecha_inicio'));
        }
        
        if ($request->filled('fecha_fin')) {
            $query->where('Fecha Presentada CC', '<=', $request->input('fecha_fin'));
        }
        
        // Ordenación - CORREGIDO: usando los mismos nombres de parámetros que la vista
        $sortBy = $request->input('sort_by', 'Id CC');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $items = $query->paginate(25)->appends($request->query());
        
        // Datos para los select
        $soes = DatosSOE::orderBy('Id SOE', 'desc')->get();
        $capacidades = Capacidad::orderBy('Capacidad')->get();
        $momentosCC = MomentoCC::orderBy('Momento CC')->get();
        $lineasCredito = CreditLine::orderBy('Linea de Crédito')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('datos-cc.index', compact(
            'items', 'soes', 'capacidades', 'momentosCC', 'lineasCredito', 'monedas'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $soes = DatosSOE::orderBy('Id SOE', 'desc')->get();
        $capacidades = Capacidad::orderBy('Capacidad')->get();
        $momentosCC = MomentoCC::orderBy('Momento CC')->get();
        $lineasCredito = CreditLine::orderBy('Linea de Crédito')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('datos-cc.create', compact(
            'soes', 'capacidades', 'momentosCC', 'lineasCredito', 'monedas'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'No CC' => 'nullable|string|max:20',
            'Id SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
            'Valor CC' => 'nullable|numeric',
            'Id Capacidad' => 'nullable|exists:Nomenclador Capacidades,Id Capacidad',
            'Fecha Pedido Cap' => 'nullable|date',
            'Fecha Asignada Cap' => 'nullable|date',
            'Fecha Presentada CC' => 'nullable|date',
            'Fecha Apertura CC' => 'nullable|date',
            'Observaciones CC' => 'nullable|string',
            'Id MomentoCC' => 'nullable|exists:Nomenclador Momentos CC,Id MomentoCC',
            'Año finan' => 'nullable|integer',
            'Id Linea credito' => 'nullable|exists:Nomenclador Líneas Crédito,Id Lineacredito',
        ]);
        
        // Asegurar nombres de campo correctos
        $data = [];
        foreach ($validated as $key => $value) {
            $data[$key] = $value;
        }
        
        DatosCC::create($data);
        
        return redirect()->route('datos-cc.index')
            ->with('success', 'Certificado de Compra creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = DatosCC::with(['moneda', 'capacidad', 'momentoCC', 'lineaCredito'])
            ->findOrFail($id);
        
        return view('datos-cc.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = DatosCC::findOrFail($id);
        
        $soes = DatosSOE::orderBy('Id SOE', 'desc')->get();
        $capacidades = Capacidad::orderBy('Capacidad')->get();
        $momentosCC = MomentoCC::orderBy('Momento CC')->get();
        $lineasCredito = CreditLine::orderBy('Linea de Crédito')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('datos-cc.edit', compact(
            'item', 'soes', 'capacidades', 'momentosCC', 'lineasCredito', 'monedas'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = DatosCC::findOrFail($id);
        
        $validated = $request->validate([
            'No CC' => 'nullable|string|max:20',
            'Id SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
            'Valor CC' => 'nullable|numeric',
            'Id Capacidad' => 'nullable|exists:Nomenclador Capacidades,Id Capacidad',
            'Fecha Pedido Cap' => 'nullable|date',
            'Fecha Asignada Cap' => 'nullable|date',
            'Fecha Presentada CC' => 'nullable|date',
            'Fecha Apertura CC' => 'nullable|date',
            'Observaciones CC' => 'nullable|string',
            'Id MomentoCC' => 'nullable|exists:Nomenclador Momentos CC,Id MomentoCC',
            'Año finan' => 'nullable|integer',
            'Id Linea credito' => 'nullable|exists:Nomenclador Líneas Crédito,Id Lineacredito',
        ]);
        
        $item->update($validated);
        
        return redirect()->route('datos-cc.index')
            ->with('success', 'Certificado de Compra actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = DatosCC::findOrFail($id);
        $item->delete();
        
        return redirect()->route('datos-cc.index')
            ->with('success', 'Certificado de Compra eliminado exitosamente.');
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = DatosCC::with(['moneda', 'capacidad', 'momentoCC', 'lineaCredito']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id CC', $ids);
            }
        }
        
        $items = $query->get();
        
        return Excel::download(new DatosCCExport($items), 'datos-cc.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = DatosCC::with(['moneda', 'capacidad', 'momentoCC', 'lineaCredito']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id CC', $ids);
            }
        }
        
        $items = $query->get();
        
        $pdf = PDF::loadView('datos-cc.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('datos-cc.pdf');
    }
    
    public function print(Request $request)
    {
        $query = DatosCC::with(['moneda', 'capacidad', 'momentoCC', 'lineaCredito']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id CC', $ids);
            }
        }
        
        $items = $query->get();
        
        return view('datos-cc.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Datos CC,Id CC'
        ]);
        
        $ids = $request->ids;
        $count = DatosCC::whereIn('Id CC', $ids)->delete();
        
        return redirect()->route('datos-cc.index')
            ->with('success', "Se eliminaron $count certificados correctamente.");
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('No_CC')) {
            $query->where('No CC', 'like', '%' . $request->input('No_CC') . '%');
        }
        
        if ($request->filled('Id_SOE')) {
            $query->where('Id SOE', $request->input('Id_SOE'));
        }
        
        if ($request->filled('Id_Capacidad')) {
            $query->where('Id Capacidad', $request->input('Id_Capacidad'));
        }
        
        if ($request->filled('Id_MomentoCC')) {
            $query->where('Id MomentoCC', $request->input('Id_MomentoCC'));
        }
        
        if ($request->filled('Id_Linea_credito')) {
            $query->where('Id Linea credito', $request->input('Id_Linea_credito'));
        }
        
        if ($request->filled('Año_finan')) {
            $query->where('Año finan', $request->input('Año_finan'));
        }
        
        // Filtros de fecha - CORREGIDOS
        if ($request->filled('fecha_inicio')) {
            $query->where('Fecha Presentada CC', '>=', $request->input('fecha_inicio'));
        }
        
        if ($request->filled('fecha_fin')) {
            $query->where('Fecha Presentada CC', '<=', $request->input('fecha_fin'));
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($request->input('sort_by'), $sortDir);
        } else {
            $query->orderBy('Id CC', 'desc');
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('No_CC')) {
            $filtros[] = "No CC: " . $request->input('No_CC');
        }
        
        if ($request->filled('Id_SOE')) {
            $filtros[] = "ID SOE: " . $request->input('Id_SOE');
        }
        
        if ($request->filled('Id_Capacidad')) {
            $capacidad = Capacidad::find($request->input('Id_Capacidad'));
            $filtros[] = "Capacidad: " . ($capacidad ? $capacidad->Capacidad : 'Desconocido');
        }
        
        if ($request->filled('Id_MomentoCC')) {
            $momento = MomentoCC::find($request->input('Id_MomentoCC'));
            $filtros[] = "Momento CC: " . ($momento ? $momento->{'Momento CC'} : 'Desconocido');
        }
        
        if ($request->filled('Id_Linea_credito')) {
            $linea = CreditLine::find($request->input('Id_Linea_credito'));
            $filtros[] = "Línea de Crédito: " . ($linea ? $linea->{'Linea de Crédito'} : 'Desconocido');
        }
        
        if ($request->filled('Año_finan')) {
            $filtros[] = "Año Financiero: " . $request->input('Año_finan');
        }
        
        if ($request->filled('fecha_inicio')) {
            $filtros[] = "Fecha desde: " . $request->input('fecha_inicio');
        }
        
        if ($request->filled('fecha_fin')) {
            $filtros[] = "Fecha hasta: " . $request->input('fecha_fin');
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->input('sort_by') . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}