<?php

namespace App\Http\Controllers;

use App\Models\DatosPersonalCubano;
use App\Models\Provider;
use App\Models\TipoCttoCubano;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DatosPersonalCubanoExport;
use PDF;

class DatosPersonalCubanoController extends Controller
{
    public function index(Request $request)
    {
        $query = DatosPersonalCubano::with(['provider', 'tipoCtto']);
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Funcionario_cubano', 'like', "%{$search}%")
                  ->orWhere('Carnet_Acorec', 'like', "%{$search}%")
                  ->orWhere('Email', 'like', "%{$search}%")
                  ->orWhere('telef', 'like', "%{$search}%")
                  ->orWhere('Cargo', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('proveedor')) {
            $query->where('Id_Proveedor', $request->proveedor);
        }
        
        if ($request->filled('activo')) {
            $query->where('Activo', $request->activo);
        }
        
        if ($request->filled('id_tipo_ctto')) {
            $query->where('Id_Tipo_Ctto', $request->id_tipo_ctto);
        }
        
        if ($request->filled('vigencia_desde')) {
            $query->whereDate('Vigencia', '>=', $request->vigencia_desde);
        }
        
        if ($request->filled('vigencia_hasta')) {
            $query->whereDate('Vigencia', '<=', $request->vigencia_hasta);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $items = $query->paginate(20)->appends($request->query());
        
        // Obtener datos para filtros
        $providers = Provider::orderBy('Proveedor')->get();
        $tiposContrato = TipoCttoCubano::orderBy('Tipo Ctto')->get();
        
        return view('datos-personal-cubano.index', compact('items', 'providers', 'tiposContrato'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $providers = Provider::orderBy('Proveedor')->get();
        $tiposContrato = TipoCttoCubano::orderBy('Tipo Ctto')->get();
        
        return view('datos-personal-cubano.create', compact('providers', 'tiposContrato'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Funcionario_cubano' => 'required|string|max:255',
            'Id_Proveedor' => 'nullable|exists:Nomenclador Proveedores,Id Proveedor',
            'telef' => 'nullable|string|max:50',
            'Email' => 'nullable|email|max:255',
            'Carnet_Acorec' => 'nullable|string|max:50',
            'Vigencia' => 'nullable|date',
            'Id_Tipo_Ctto' => 'nullable|exists:Nomenclador de Tipos de contratos cubanos,Id Tipo Ctto',
            'Activo' => 'nullable|boolean',
            'Cargo' => 'nullable|string|max:255',
            'Direccion' => 'nullable|string|max:500',
            'Observaciones' => 'nullable|string',
        ]);

        DatosPersonalCubano::create($validated);
        
        return redirect()->route('datos-personal-cubano.index')
            ->with('success', 'Personal cubano creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = DatosPersonalCubano::with(['provider', 'tipoCtto'])->findOrFail($id);
        return view('datos-personal-cubano.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $item = DatosPersonalCubano::findOrFail($id);
        $providers = Provider::orderBy('Proveedor')->get();
        $tiposContrato = TipoCttoCubano::orderBy('Tipo Ctto')->get();
        
        return view('datos-personal-cubano.edit', compact('item', 'providers', 'tiposContrato'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = DatosPersonalCubano::findOrFail($id);
        
        $validated = $request->validate([
            'Funcionario_cubano' => 'required|string|max:255',
            'Id_Proveedor' => 'nullable|exists:Nomenclador Proveedores,Id Proveedor',
            'telef' => 'nullable|string|max:50',
            'Email' => 'nullable|email|max:255',
            'Carnet_Acorec' => 'nullable|string|max:50',
            'Vigencia' => 'nullable|date',
            'Id_Tipo_Ctto' => 'nullable|exists:Nomenclador de Tipos de contratos cubanos,Id Tipo Ctto',
            'Activo' => 'nullable|boolean',
            'Cargo' => 'nullable|string|max:255',
            'Direccion' => 'nullable|string|max:500',
            'Observaciones' => 'nullable|string',
        ]);

        $item->update($validated);
        
        return redirect()->route('datos-personal-cubano.index')
            ->with('success', 'Personal cubano actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = DatosPersonalCubano::findOrFail($id);
        $item->delete();
        
        return redirect()->route('datos-personal-cubano.index')
            ->with('success', 'Personal cubano eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros para eliminar.');
        }
        
        $count = DatosPersonalCubano::whereIn('Id', $ids)->delete();
        
        return redirect()->route('datos-personal-cubano.index')
            ->with('success', "Se eliminaron $count registros correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = DatosPersonalCubano::with(['provider', 'tipoCtto']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        return Excel::download(new DatosPersonalCubanoExport($items), 'personal-cubano.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = DatosPersonalCubano::with(['provider', 'tipoCtto']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        // Configurar PDF en orientación horizontal
        $pdf = PDF::loadView('datos-personal-cubano.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('personal-cubano.pdf');
    }

    public function print(Request $request)
    {
        $query = DatosPersonalCubano::with(['provider', 'tipoCtto']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        return view('datos-personal-cubano.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Funcionario_cubano', 'like', "%{$search}%")
                  ->orWhere('Carnet_Acorec', 'like', "%{$search}%")
                  ->orWhere('Email', 'like', "%{$search}%")
                  ->orWhere('telef', 'like', "%{$search}%")
                  ->orWhere('Cargo', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('proveedor')) {
            $query->where('Id_Proveedor', $request->proveedor);
        }
        
        if ($request->filled('activo')) {
            $query->where('Activo', $request->activo);
        }
        
        if ($request->filled('id_tipo_ctto')) {
            $query->where('Id_Tipo_Ctto', $request->id_tipo_ctto);
        }
        
        if ($request->filled('vigencia_desde')) {
            $query->whereDate('Vigencia', '>=', $request->vigencia_desde);
        }
        
        if ($request->filled('vigencia_hasta')) {
            $query->whereDate('Vigencia', '<=', $request->vigencia_hasta);
        }
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('proveedor')) {
            $proveedor = Provider::find($request->proveedor);
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor : $request->proveedor);
        }
        
        if ($request->filled('activo')) {
            $filtros[] = "Activo: " . ($request->activo == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('id_tipo_ctto')) {
            $tipoCtto = TipoCttoCubano::find($request->id_tipo_ctto);
            $filtros[] = "Tipo Contrato: " . ($tipoCtto ? $tipoCtto->{'Tipo Ctto'} : $request->id_tipo_ctto);
        }
        
        if ($request->filled('vigencia_desde') && $request->filled('vigencia_hasta')) {
            $filtros[] = "Vigencia: " . $request->vigencia_desde . " a " . $request->vigencia_hasta;
        } elseif ($request->filled('vigencia_desde')) {
            $filtros[] = "Vigencia desde: " . $request->vigencia_desde;
        } elseif ($request->filled('vigencia_hasta')) {
            $filtros[] = "Vigencia hasta: " . $request->vigencia_hasta;
        }
        
        return $filtros;
    }
}