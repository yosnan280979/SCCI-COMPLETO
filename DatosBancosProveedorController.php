<?php

namespace App\Http\Controllers;

use App\Models\DatosBancosProveedor;
use App\Models\Provider;
use App\Models\Banco;
use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DatosBancosProveedorExport;
use PDF;

class DatosBancosProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = DatosBancosProveedor::with(['proveedor', 'banco', 'moneda']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Numero Cuenta', 'like', "%{$search}%")
                  ->orWhere('Titular', 'like', "%{$search}%")
                  ->orWhere('SWIFT_BIC', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%");
            });
        }

        if ($request->filled('proveedor')) {
            $query->where('Id proveedor', $request->proveedor);
        }

        if ($request->filled('banco')) {
            $query->where('Id banco', $request->banco);
        }

        if ($request->filled('moneda')) {
            $query->where('Id moneda', $request->moneda);
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $datosBancos = $query->paginate(20)->appends($request->query());

        $proveedores = Provider::orderBy('Proveedor')->get();
        $bancos = Banco::orderBy('Banco')->get();
        $monedas = Currency::orderBy('Moneda')->get();

        return view('datos_bancos_proveedores.index', compact('datosBancos', 'proveedores', 'bancos', 'monedas'));
    }

    public function create()
    {
        $proveedores = Provider::orderBy('Proveedor')->get();
        $bancos = Banco::orderBy('Banco')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('datos_bancos_proveedores.create', compact('proveedores', 'bancos', 'monedas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Id banco' => 'required|exists:Nomenclador Bancos,Id Banco',
            'Id moneda' => 'required|exists:Nomenclador Monedas,Id Moneda',
            'Numero Cuenta' => 'required|string|max:255',
            'Titular' => 'nullable|string|max:255',
            'Tipo Cuenta' => 'nullable|string|max:50',
            'SWIFT_BIC' => 'nullable|string|max:20',
            'Direccion Banco' => 'nullable|string|max:255',
            'Telefono Banco' => 'nullable|string|max:50',
            'Email Banco' => 'nullable|string|email|max:255',
            'Contacto Banco' => 'nullable|string|max:100',
            'Observaciones' => 'nullable|string',
            'Activo' => 'nullable|boolean',
        ]);

        DatosBancosProveedor::create($validated);
        
        return redirect()->route('datos-bancos-proveedores.index')
            ->with('success', 'Datos bancarios creados exitosamente.');
    }

    public function show($id)
    {
        $dato = DatosBancosProveedor::with(['proveedor', 'banco', 'moneda'])->findOrFail($id);
        return view('datos_bancos_proveedores.show', compact('dato'));
    }

    public function edit($id)
    {
        $dato = DatosBancosProveedor::findOrFail($id);
        $proveedores = Provider::orderBy('Proveedor')->get();
        $bancos = Banco::orderBy('Banco')->get();
        $monedas = Currency::orderBy('Moneda')->get();
        
        return view('datos_bancos_proveedores.edit', compact('dato', 'proveedores', 'bancos', 'monedas'));
    }

    public function update(Request $request, $id)
    {
        $dato = DatosBancosProveedor::findOrFail($id);
        
        $validated = $request->validate([
            'Id proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Id banco' => 'required|exists:Nomenclador Bancos,Id Banco',
            'Id moneda' => 'required|exists:Nomenclador Monedas,Id Moneda',
            'Numero Cuenta' => 'required|string|max:255',
            'Titular' => 'nullable|string|max:255',
            'Tipo Cuenta' => 'nullable|string|max:50',
            'SWIFT_BIC' => 'nullable|string|max:20',
            'Direccion Banco' => 'nullable|string|max:255',
            'Telefono Banco' => 'nullable|string|max:50',
            'Email Banco' => 'nullable|string|email|max:255',
            'Contacto Banco' => 'nullable|string|max:100',
            'Observaciones' => 'nullable|string',
            'Activo' => 'nullable|boolean',
        ]);

        $dato->update($validated);
        
        return redirect()->route('datos-bancos-proveedores.index')
            ->with('success', 'Datos bancarios actualizados exitosamente.');
    }

    public function destroy($id)
    {
        $dato = DatosBancosProveedor::findOrFail($id);
        $dato->delete();
        
        return redirect()->route('datos-bancos-proveedores.index')
            ->with('success', 'Datos bancarios eliminados exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros para eliminar.');
        }
        
        $count = DatosBancosProveedor::whereIn('Id', $ids)->delete();
        
        return redirect()->route('datos-bancos-proveedores.index')
            ->with('success', "Se eliminaron $count registros correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = DatosBancosProveedor::with(['proveedor', 'banco', 'moneda']);

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
        
        $datosBancos = $query->orderBy('Id')->get();
        
        return Excel::download(new DatosBancosProveedorExport($datosBancos), 'datos-bancos-proveedores.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = DatosBancosProveedor::with(['proveedor', 'banco', 'moneda']);

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
        
        $datosBancos = $query->orderBy('Id')->get();
        
        // Limitar registros para evitar timeout
        if ($datosBancos->count() > 100) {
            $datosBancos = $datosBancos->take(100);
        }
        
        $pdf = PDF::loadView('datos_bancos_proveedores.pdf', [
            'datosBancos' => $datosBancos,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');
        
        // Configurar timeout para PDF
        $pdf->setOptions([
            'enable_html5_parser' => true,
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);
        
        return $pdf->download('datos-bancos-proveedores.pdf');
    }

    public function print(Request $request)
    {
        $query = DatosBancosProveedor::with(['proveedor', 'banco', 'moneda']);

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
        
        $datosBancos = $query->orderBy('Id')->get();
        
        // Limitar registros para evitar problemas de rendimiento
        if ($datosBancos->count() > 100) {
            $datosBancos = $datosBancos->take(100);
        }
        
        return view('datos_bancos_proveedores.print', [
            'datosBancos' => $datosBancos,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Numero Cuenta', 'like', "%{$search}%")
                  ->orWhere('Titular', 'like', "%{$search}%")
                  ->orWhere('SWIFT_BIC', 'like', "%{$search}%")
                  ->orWhere('Observaciones', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('proveedor')) {
            $query->where('Id proveedor', $request->proveedor);
        }
        
        if ($request->filled('banco')) {
            $query->where('Id banco', $request->banco);
        }
        
        if ($request->filled('moneda')) {
            $query->where('Id moneda', $request->moneda);
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
        
        if ($request->filled('banco')) {
            $banco = Banco::find($request->banco);
            $filtros[] = "Banco: " . ($banco ? $banco->Banco : $request->banco);
        }
        
        if ($request->filled('moneda')) {
            $moneda = Currency::find($request->moneda);
            $filtros[] = "Moneda: " . ($moneda ? $moneda->Moneda : $request->moneda);
        }
        
        return $filtros;
    }
}