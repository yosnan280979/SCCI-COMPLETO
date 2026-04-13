<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Provider;
use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContratosExport;
use PDF;

class ContratoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Contrato::query();
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('No Ctto', 'like', "%{$search}%")
                  ->orWhere('Forma de Pago', 'like', "%{$search}%")
                  ->orWhere('Observaciones Esp', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('no_ctto')) {
            $query->where('No Ctto', 'like', "%{$request->no_ctto}%");
        }
        
        if ($request->filled('id_proveedor')) {
            $query->where('Id Proveedor', $request->id_proveedor);
        }
        
        if ($request->filled('id_moneda')) {
            $query->where('Id Moneda', $request->id_moneda);
        }
        
        if ($request->filled('forma_pago')) {
            $query->where('Forma de Pago', 'like', "%{$request->forma_pago}%");
        }
        
        if ($request->filled('concluido')) {
            $query->where('Concluido', $request->concluido);
        }
        
        if ($request->filled('cancelado')) {
            $query->where('Cancelado', $request->cancelado);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Ctto', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Ctto', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('valor_min_cuc')) {
            $query->where('Valor Ctto CUC', '>=', $request->valor_min_cuc);
        }
        
        if ($request->filled('valor_max_cuc')) {
            $query->where('Valor Ctto CUC', '<=', $request->valor_max_cuc);
        }
        
        if ($request->filled('valor_min_mon_prov')) {
            $query->where('Valor Ctto Mon Prov', '>=', $request->valor_min_mon_prov);
        }
        
        if ($request->filled('valor_max_mon_prov')) {
            $query->where('Valor Ctto Mon Prov', '<=', $request->valor_max_mon_prov);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Ctto');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $contratos = $query->paginate(20)->appends($request->query());
        
        // Obtener datos para filtros
        $providers = Provider::where('Activo', 1)->orderBy('Proveedor')->get();
        $currencies = Currency::orderBy('Moneda')->get();
        
        return view('contratos.index', compact('contratos', 'providers', 'currencies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $providers = Provider::where('Activo', 1)->orderBy('Proveedor')->get();
        $currencies = Currency::orderBy('Moneda')->get();
        
        return view('contratos.create', compact('providers', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'No Ctto' => 'required|unique:Contratos,No Ctto',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Valor Ctto Mon Prov' => 'nullable|numeric',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
            'Forma de Pago' => 'nullable|string|max:50',
            'Valor Ctto CUC' => 'nullable|numeric',
            'Concluido' => 'nullable|boolean',
            'Observaciones Esp' => 'nullable|string',
            'Cancelado' => 'nullable|boolean',
        ]);
        
        Contrato::create($validated);
        
        return redirect()->route('contratos.index')
            ->with('success', 'Contrato creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $contrato = Contrato::with(['provider', 'currency'])->findOrFail($id);
        return view('contratos.show', compact('contrato'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $contrato = Contrato::findOrFail($id);
        $providers = Provider::where('Activo', 1)->orderBy('Proveedor')->get();
        $currencies = Currency::orderBy('Moneda')->get();
        
        return view('contratos.edit', compact('contrato', 'providers', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $contrato = Contrato::findOrFail($id);
        
        $validated = $request->validate([
            'No Ctto' => 'required|unique:Contratos,No Ctto,' . $contrato->{'Id Ctto'} . ',Id Ctto',
            'Id Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Valor Ctto Mon Prov' => 'nullable|numeric',
            'Id Moneda' => 'nullable|exists:Nomenclador Monedas,Id Moneda',
            'Forma de Pago' => 'nullable|string|max:50',
            'Valor Ctto CUC' => 'nullable|numeric',
            'Concluido' => 'nullable|boolean',
            'Observaciones Esp' => 'nullable|string',
            'Cancelado' => 'nullable|boolean',
        ]);
        
        $contrato->update($validated);
        
        return redirect()->route('contratos.index')
            ->with('success', 'Contrato actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $contrato = Contrato::findOrFail($id);
        $contrato->delete();
        
        return redirect()->route('contratos.index')
            ->with('success', 'Contrato eliminado exitosamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron contratos para eliminar.');
        }
        
        $count = Contrato::whereIn('Id Ctto', $ids)->delete();
        
        return redirect()->route('contratos.index')
            ->with('success', "Se eliminaron $count contratos correctamente.");
    }
    
    // Métodos de exportación
    
    public function exportExcel(Request $request)
    {
        $query = Contrato::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ctto', $ids);
            }
        }
        
        $contratos = $query->orderBy('Id Ctto')->get();
        
        return Excel::download(new ContratosExport($contratos), 'contratos.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Contrato::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ctto', $ids);
            }
        }
        
        $contratos = $query->orderBy('Id Ctto')->get();
        
        $pdf = PDF::loadView('contratos.pdf', [
            'contratos' => $contratos,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('contratos.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Contrato::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ctto', $ids);
            }
        }
        
        $contratos = $query->orderBy('Id Ctto')->get();
        
        return view('contratos.print', [
            'contratos' => $contratos,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('No Ctto', 'like', "%{$search}%")
                  ->orWhere('Forma de Pago', 'like', "%{$search}%")
                  ->orWhere('Observaciones Esp', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('no_ctto')) {
            $query->where('No Ctto', 'like', "%{$request->no_ctto}%");
        }
        
        if ($request->filled('id_proveedor')) {
            $query->where('Id Proveedor', $request->id_proveedor);
        }
        
        if ($request->filled('id_moneda')) {
            $query->where('Id Moneda', $request->id_moneda);
        }
        
        if ($request->filled('forma_pago')) {
            $query->where('Forma de Pago', 'like', "%{$request->forma_pago}%");
        }
        
        if ($request->filled('concluido')) {
            $query->where('Concluido', $request->concluido);
        }
        
        if ($request->filled('cancelado')) {
            $query->where('Cancelado', $request->cancelado);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Ctto', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Ctto', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('valor_min_cuc')) {
            $query->where('Valor Ctto CUC', '>=', $request->valor_min_cuc);
        }
        
        if ($request->filled('valor_max_cuc')) {
            $query->where('Valor Ctto CUC', '<=', $request->valor_max_cuc);
        }
        
        if ($request->filled('valor_min_mon_prov')) {
            $query->where('Valor Ctto Mon Prov', '>=', $request->valor_min_mon_prov);
        }
        
        if ($request->filled('valor_max_mon_prov')) {
            $query->where('Valor Ctto Mon Prov', '<=', $request->valor_max_mon_prov);
        }
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('no_ctto')) {
            $filtros[] = "No. Contrato: " . $request->no_ctto;
        }
        
        if ($request->filled('id_proveedor')) {
            $provider = Provider::find($request->id_proveedor);
            $filtros[] = "Proveedor: " . ($provider ? $provider->Proveedor : 'Desconocido');
        }
        
        if ($request->filled('id_moneda')) {
            $currency = Currency::find($request->id_moneda);
            $filtros[] = "Moneda: " . ($currency ? $currency->Moneda : 'Desconocido');
        }
        
        if ($request->filled('forma_pago')) {
            $filtros[] = "Forma de Pago: " . $request->forma_pago;
        }
        
        if ($request->filled('concluido')) {
            $filtros[] = "Concluido: " . ($request->concluido == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('cancelado')) {
            $filtros[] = "Cancelado: " . ($request->cancelado == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $filtros[] = "Fecha: " . $request->fecha_desde . " a " . $request->fecha_hasta;
        } elseif ($request->filled('fecha_desde')) {
            $filtros[] = "Fecha desde: " . $request->fecha_desde;
        } elseif ($request->filled('fecha_hasta')) {
            $filtros[] = "Fecha hasta: " . $request->fecha_hasta;
        }
        
        if ($request->filled('valor_min_cuc') && $request->filled('valor_max_cuc')) {
            $filtros[] = "Valor CUC: " . $request->valor_min_cuc . " a " . $request->valor_max_cuc;
        } elseif ($request->filled('valor_min_cuc')) {
            $filtros[] = "Valor CUC mínimo: " . $request->valor_min_cuc;
        } elseif ($request->filled('valor_max_cuc')) {
            $filtros[] = "Valor CUC máximo: " . $request->valor_max_cuc;
        }
        
        if ($request->filled('valor_min_mon_prov') && $request->filled('valor_max_mon_prov')) {
            $filtros[] = "Valor Mon. Prov.: " . $request->valor_min_mon_prov . " a " . $request->valor_max_mon_prov;
        } elseif ($request->filled('valor_min_mon_prov')) {
            $filtros[] = "Valor Mon. Prov. mínimo: " . $request->valor_min_mon_prov;
        } elseif ($request->filled('valor_max_mon_prov')) {
            $filtros[] = "Valor Mon. Prov. máximo: " . $request->valor_max_mon_prov;
        }
        
        return $filtros;
    }
}