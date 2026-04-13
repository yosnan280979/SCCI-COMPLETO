<?php

namespace App\Http\Controllers;

use App\Models\ClienteFgne;
use App\Exports\ClientesFgneExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ClienteFgneController extends Controller
{
    /**
     * Muestra la lista de clientes FGNE con filtros y paginación.
     */
    public function index(Request $request)
    {
        $query = ClienteFgne::query();

        // Filtro de búsqueda general
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%")
                  ->orWhere('fgne_codigo', 'like', "%$search%");
            });
        }

        // Filtro por Código FGNE (TCP/CNA/MIPYME)
        if ($request->filled('fgne_codigo')) {
            $query->where('fgne_codigo', $request->input('fgne_codigo'));
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');
        
        $items = $query->orderBy($sortBy, $sortDir)->paginate(25);

        // Lista única de códigos para el dropdown del filtro
        $codigos = ClienteFgne::select('fgne_codigo')
            ->whereNotNull('fgne_codigo')
            ->distinct()
            ->pluck('fgne_codigo')
            ->sort()
            ->values();

        return view('clientes-fgne.index', compact('items', 'codigos'));
    }

    public function create()
    {
        return view('clientes-fgne.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'no_registro' => 'nullable|integer',
            'fgne_codigo' => 'nullable|string|max:50',
            'codigo_nit' => 'nullable|string|max:50',
            'objeto_social' => 'nullable|string',
            'direccion' => 'nullable|string',
            'telefonos' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'cuenta_mn' => 'nullable|string|max:50',
            'cuenta_usd' => 'nullable|string|max:50',
            'cuenta_mlc' => 'nullable|string|max:50',
            'sucursal_banco' => 'nullable|string|max:255',
            'representacion' => 'nullable|string|max:255',
            'ficha' => 'nullable|string|max:50',
            'actualiz' => 'nullable|string|max:50',
            'bases_generales' => 'nullable|string|max:50',
            'fecha_actualizacion' => 'nullable|date',
            'ctto_consignacion' => 'nullable|string|max:50',
            'fecha_ctto_consignacion' => 'nullable|date',
            'ctto_in_bond' => 'nullable|string|max:50',
            'fecha_ctto_in_bond' => 'nullable|date',
        ]);

        ClienteFgne::create($validated);
        
        return redirect()->route('clientes-fgne.index')
                        ->with('success', 'Cliente FGNE creado exitosamente.');
    }

    public function show($id)
    {
        $item = ClienteFgne::findOrFail($id);
        return view('clientes-fgne.show', compact('item'));
    }

    public function edit($id)
    {
        $item = ClienteFgne::findOrFail($id);
        return view('clientes-fgne.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ClienteFgne::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'fgne_codigo' => 'nullable|string|max:50',
            'codigo_nit' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $item->update($validated);
        
        return redirect()->route('clientes-fgne.index')
                        ->with('success', 'Actualizado correctamente.');
    }

    public function destroy($id)
    {
        $item = ClienteFgne::findOrFail($id);
        $item->delete();
        
        return redirect()->route('clientes-fgne.index')
                        ->with('success', 'Eliminado correctamente.');
    }

    public function exportExcel(Request $request)
    {
        $query = ClienteFgne::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%")
                  ->orWhere('fgne_codigo', 'like', "%$search%");
            });
        }
        if ($request->filled('fgne_codigo')) {
            $query->where('fgne_codigo', $request->input('fgne_codigo'));
        }
        
        $items = $query->get();
        
        return Excel::download(new ClientesFgneExport($items), 'clientes_fgne.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = ClienteFgne::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%")
                  ->orWhere('fgne_codigo', 'like', "%$search%");
            });
        }
        if ($request->filled('fgne_codigo')) {
            $query->where('fgne_codigo', $request->input('fgne_codigo'));
        }
        
        $items = $query->get();
        
        $pdf = Pdf::loadView('clientes-fgne.pdf', compact('items'));
        return $pdf->download('clientes_fgne.pdf');
    }

    public function print(Request $request)
    {
        $query = ClienteFgne::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                  ->orWhere('codigo_nit', 'like', "%$search%")
                  ->orWhere('fgne_codigo', 'like', "%$search%");
            });
        }
        if ($request->filled('fgne_codigo')) {
            $query->where('fgne_codigo', $request->input('fgne_codigo'));
        }
        
        $items = $query->get();
        
        return view('clientes-fgne.print', compact('items'));
    }
}
