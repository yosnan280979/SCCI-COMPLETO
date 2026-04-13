<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OSDE;
use App\Exports\OSDEExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class OSDEController extends Controller
{
    public function index(Request $request)
    {
        $query = OSDE::query();

        // 🔎 Filtro de búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('Id Osde', 'like', "%{$search}%")
                  ->orWhere('OSDE', 'like', "%{$search}%");
            });
        }

        // 🔎 Orden dinámico
        if ($orderBy = $request->input('order_by')) {
            $direction = $request->input('order_direction', 'asc');
            $query->orderBy($orderBy, $direction);
        } else {
            $query->orderBy('Id Osde', 'asc');
        }

        // ✅ Paginación
        $osdes = $query->paginate(10)->withQueryString();

        return view('nomencladores.osdes.index', compact('osdes'));
    }

    public function create()
    {
        return view('nomencladores.osdes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'OSDE' => 'required|string|max:255',
            'MICONS' => 'nullable|boolean',
        ]);

        OSDE::create($request->all());

        return redirect()->route('nomencladores.osde.index')
                         ->with('success', 'OSDE creado correctamente.');
    }

    public function show($id)
    {
        $osde = OSDE::findOrFail($id);
        return view('nomencladores.osdes.show', compact('osde'));
    }

    public function edit($id)
    {
        $osde = OSDE::findOrFail($id);
        return view('nomencladores.osdes.edit', compact('osde'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'OSDE' => 'required|string|max:255',
            'MICONS' => 'nullable|boolean',
        ]);

        $osde = OSDE::findOrFail($id);
        $osde->update($request->all());

        return redirect()->route('nomencladores.osde.index')
                         ->with('success', 'OSDE actualizado correctamente.');
    }

    public function destroy($id)
    {
        $osde = OSDE::findOrFail($id);
        $osde->delete();

        return redirect()->route('nomencladores.osde.index')
                         ->with('success', 'OSDE eliminado correctamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador OSDE,Id Osde',
        ]);

        $ids = $request->selected_ids;
        $count = OSDE::whereIn('Id Osde', $ids)->delete();

        return redirect()->route('nomencladores.osde.index')
                         ->with('success', "Se eliminaron $count OSDEs correctamente.");
    }

    // 📤 Exportaciones
    public function exportExcel(Request $request)
    {
        $query = OSDE::query();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Id Osde', 'like', "%{$search}%")
                  ->orWhere('OSDE', 'like', "%{$search}%");
            });
        }

        // Orden dinámico
        if ($request->filled('order_by')) {
            $direction = $request->input('order_direction', 'asc');
            $query->orderBy($request->order_by, $direction);
        }

        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Osde', $ids);
            }
        }

        $osdes = $query->get();

        return Excel::download(new OSDEExport($osdes), 'osdes.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = OSDE::query();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Id Osde', 'like', "%{$search}%")
                  ->orWhere('OSDE', 'like', "%{$search}%");
            });
        }

        // Orden dinámico
        if ($request->filled('order_by')) {
            $direction = $request->input('order_direction', 'asc');
            $query->orderBy($request->order_by, $direction);
        }

        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Osde', $ids);
            }
        }

        $osdes = $query->get();

        $pdf = PDF::loadView('nomencladores.osdes.pdf', [
            'osdes' => $osdes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('osdes.pdf');
    }

    public function print(Request $request)
    {
        $query = OSDE::query();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Id Osde', 'like', "%{$search}%")
                  ->orWhere('OSDE', 'like', "%{$search}%");
            });
        }

        // Orden dinámico
        if ($request->filled('order_by')) {
            $direction = $request->input('order_direction', 'asc');
            $query->orderBy($request->order_by, $direction);
        }

        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Osde', $ids);
            }
        }

        $osdes = $query->get();

        return view('nomencladores.osdes.print', [
            'osdes' => $osdes,
            'filtros' => $this->obtenerFiltros($request)
        ]);
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
            $orden = "Ordenado por: " . $request->order_by . " (" . ($request->order_direction == 'asc' ? 'Ascendente' : 'Descendente') . ")";
            $filtros[] = $orden;
        }
        
        return $filtros;
    }
}