<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CurrenciesExport;
use PDF;

class CurrencyController extends Controller
{
    /**
     * Mostrar listado de monedas con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = Currency::query();

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $query->where('Moneda', 'like', '%' . $request->search . '%');
        }

        // Ordenar
        if ($request->filled('order_by') && $request->filled('order_direction')) {
            $query->orderBy($request->order_by, $request->order_direction);
        } else {
            $query->orderBy('Moneda', 'asc');
        }

        // Paginación (ejemplo: 10 por página)
        $currencies = $query->paginate(10)->appends($request->query());

        return view('nomencladores.currencies.index', compact('currencies'));
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        return view('nomencladores.currencies.create');
    }

    /**
     * Guardar nueva moneda
     */
    public function store(Request $request)
    {
        $request->validate([
            'Moneda' => 'required|string|max:50|unique:Nomenclador Monedas,Moneda'
        ]);

        Currency::create($request->all());

        return redirect()->route('nomencladores.currencies.index')
            ->with('success', 'Moneda creada correctamente');
    }

    /**
     * Mostrar moneda
     */
    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        return view('nomencladores.currencies.show', compact('currency'));
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $currency = Currency::findOrFail($id);
        return view('nomencladores.currencies.edit', compact('currency'));
    }

    /**
     * Actualizar moneda
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'Moneda' => 'required|string|max:50|unique:Nomenclador Monedas,Moneda,' . $id . ',Id Moneda'
        ]);

        $currency = Currency::findOrFail($id);
        $currency->update($request->all());

        return redirect()->route('nomencladores.currencies.index')
            ->with('success', 'Moneda actualizada correctamente');
    }

    /**
     * Eliminar moneda
     */
    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        $currency->delete();

        return redirect()->route('nomencladores.currencies.index')
            ->with('success', 'Moneda eliminada correctamente');
    }

    /**
     * Eliminar múltiples monedas
     */
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Nomenclador Monedas,Id Moneda'
        ]);

        $ids = $request->ids;
        $count = Currency::whereIn('Id Moneda', $ids)->delete();

        return redirect()->route('nomencladores.currencies.index')
            ->with('success', "Se eliminaron $count monedas correctamente.");
    }

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Currency::orderBy('Moneda');

        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Moneda', $ids);
            }
        }

        $currencies = $query->get();

        return Excel::download(
            new CurrenciesExport($currencies),
            'monedas_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Currency::orderBy('Moneda');

        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Moneda', $ids);
            }
        }

        $currencies = $query->get();

        $pdf = PDF::loadView('nomencladores.currencies.pdf', [
            'currencies' => $currencies,
            'filtros' => $this->obtenerFiltros($request)
        ]);

        return $pdf->download('monedas_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Vista para impresión
     */
    public function print(Request $request)
    {
        $query = Currency::orderBy('Moneda');

        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Moneda', $ids);
            }
        }

        $currencies = $query->get();

        return view('nomencladores.currencies.print', [
            'currencies' => $currencies,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }

    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Moneda', 'like', "%{$search}%");
        }
        
        if ($request->filled('order_by') && $request->filled('order_direction')) {
            $query->orderBy($request->order_by, $request->order_direction);
        }
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
        
        if ($request->filled('order_by')) {
            $orden = $request->order_by == 'Id Moneda' ? 'ID' : 'Nombre';
            $direccion = $request->order_direction == 'asc' ? 'Ascendente' : 'Descendente';
            $filtros[] = "Ordenado por: $orden ($direccion)";
        }
        
        return $filtros;
    }
}