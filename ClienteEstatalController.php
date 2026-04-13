<?php

namespace App\Http\Controllers;

use App\Models\ClienteEstatal;
use App\Exports\ClientesEstatalesExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ClienteEstatalController extends Controller
{
    /**
     * Muestra la lista de clientes estatales con filtros y paginación.
     */
    public function index(Request $request)
    {
        $query = ClienteEstatal::query();

        // Filtro de búsqueda general (Nombre o NIT)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre_empresa', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%");
        }

        // Filtro por Ministerio
        if ($request->filled('ministerio')) {
            $query->where('ministerio', $request->input('ministerio'));
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');
        
        $items = $query->orderBy($sortBy, $sortDir)->paginate(25);

        // Lista única de ministerios para el dropdown del filtro
        $ministerios = ClienteEstatal::select('ministerio')
            ->whereNotNull('ministerio')
            ->distinct()
            ->pluck('ministerio')
            ->sort()
            ->values();

        return view('clientes-estatales.index', compact('items', 'ministerios'));
    }

    /**
     * Muestra el formulario para crear un nuevo cliente estatal.
     */
    public function create()
    {
        return view('clientes-estatales.create');
    }

    /**
     * Guarda un nuevo cliente estatal en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'codigo_nit' => 'nullable|string|max:50',
            'ministerio' => 'nullable|string|max:255',
            'resolucion_creacion' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:255',
            'nombre_director' => 'nullable|string|max:255',
            'resolucion_director' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'cuentas_bancarias' => 'nullable|string',
            'convenios_firmados' => 'nullable|string',
            // Años opcionales
            'anno_2020' => 'nullable|string|max:50',
            'anno_2021' => 'nullable|string|max:50',
            'anno_2022' => 'nullable|string|max:50',
            'anno_2023' => 'nullable|string|max:50',
            'anno_2024' => 'nullable|string|max:50',
            'anno_2025' => 'nullable|string|max:50',
            'anno_2026' => 'nullable|string|max:50',
        ]);

        ClienteEstatal::create($validated);
        
        return redirect()->route('clientes-estatales.index')
                        ->with('success', 'Cliente Estatal creado exitosamente.');
    }

    /**
     * Muestra los detalles de un cliente específico.
     */
    public function show($id)
    {
        $item = ClienteEstatal::findOrFail($id);
        return view('clientes-estatales.show', compact('item'));
    }

    /**
     * Muestra el formulario para editar un cliente existente.
     */
    public function edit($id)
    {
        $item = ClienteEstatal::findOrFail($id);
        return view('clientes-estatales.edit', compact('item'));
    }

    /**
     * Actualiza un cliente existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $item = ClienteEstatal::findOrFail($id);
        
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'codigo_nit' => 'nullable|string|max:50',
            'ministerio' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $item->update($request->all());
        
        return redirect()->route('clientes-estatales.index')
                        ->with('success', 'Actualizado correctamente.');
    }

    /**
     * Elimina un cliente específico.
     */
    public function destroy($id)
    {
        $item = ClienteEstatal::findOrFail($id);
        $item->delete();
        
        return redirect()->route('clientes-estatales.index')
                        ->with('success', 'Eliminado correctamente.');
    }

    /**
     * Exporta a Excel aplicando los filtros actuales.
     */
    public function exportExcel(Request $request)
    {
        // Reutilizamos la lógica de filtros
        $query = ClienteEstatal::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre_empresa', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%");
        }
        if ($request->filled('ministerio')) {
            $query->where('ministerio', $request->input('ministerio'));
        }
        
        $items = $query->get();
        
        return Excel::download(new ClientesEstatalesExport($items), 'clientes_estatales.xlsx');
    }

    /**
     * Exporta a PDF aplicando los filtros actuales.
     */
    public function exportPdf(Request $request)
    {
        // Reutilizamos la lógica de filtros
        $query = ClienteEstatal::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre_empresa', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%");
        }
        if ($request->filled('ministerio')) {
            $query->where('ministerio', $request->input('ministerio'));
        }
        
        $items = $query->get();
        
        $pdf = Pdf::loadView('clientes-estatales.pdf', compact('items'));
        return $pdf->download('clientes_estatales.pdf');
    }

    /**
     * Muestra la vista de impresión aplicando los filtros actuales.
     */
    public function print(Request $request)
    {
        // Reutilizamos la lógica de filtros
        $query = ClienteEstatal::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre_empresa', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%");
        }
        if ($request->filled('ministerio')) {
            $query->where('ministerio', $request->input('ministerio'));
        }
        
        $items = $query->get();
        
        return view('clientes-estatales.print', compact('items'));
    }
}