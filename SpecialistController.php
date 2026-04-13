<?php

namespace App\Http\Controllers;

use App\Models\Specialist;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SpecialistExport;
use PDF;

class SpecialistController extends Controller
{
    public function index(Request $request)
    {
        $query = Specialist::query();

        // Filtros
        if ($request->filled('search')) {
            $query->where('Especialista', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id especialista');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $items = $query->paginate(20)->appends($request->query());
        
        return view('nomencladores.specialists.index', compact('items'));
    }

    public function create()
    {
        $tutores = Specialist::where('Activos', 1)->get();
        return view('nomencladores.specialists.create', compact('tutores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Especialista' => 'required|string|max:50|unique:Nomenclador Especialistas,Especialista',
            'Activos' => 'required|boolean',
            'Id Tutor' => 'nullable|exists:Nomenclador Especialistas,Id especialista'
        ]);

        Specialist::create($request->all());

        return redirect()->route('nomencladores.specialists.index')
            ->with('success', 'Especialista creado correctamente');
    }

    public function show($id)
    {
        $specialist = Specialist::findOrFail($id);
        return view('nomencladores.specialists.show', compact('specialist'));
    }

    public function edit($id)
    {
        $specialist = Specialist::findOrFail($id);
        $tutores = Specialist::where('Activos', 1)->where('Id especialista', '!=', $id)->get();
        return view('nomencladores.specialists.edit', compact('specialist', 'tutores'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Especialista' => 'required|string|max:50|unique:Nomenclador Especialistas,Especialista,' . $id . ',Id especialista',
            'Activos' => 'required|boolean',
            'Id Tutor' => 'nullable|exists:Nomenclador Especialistas,Id especialista'
        ]);

        $specialist = Specialist::findOrFail($id);
        $specialist->update($request->all());

        return redirect()->route('nomencladores.specialists.index')
            ->with('success', 'Especialista actualizado correctamente');
    }

    public function destroy($id)
    {
        $specialist = Specialist::findOrFail($id);
        $specialist->delete();

        return redirect()->route('nomencladores.specialists.index')
            ->with('success', 'Especialista eliminado correctamente');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron especialistas para eliminar.');
        }
        
        $count = Specialist::whereIn('Id especialista', $ids)->delete();

        return redirect()->route('nomencladores.specialists.index')
            ->with('success', "Se eliminaron $count especialista(s) correctamente");
    }

    public function exportExcel(Request $request)
    {
        $query = Specialist::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('Especialista', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }

        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id especialista', $ids);
            }
        }

        $items = $query->orderBy('Id especialista')->get();
        
        return Excel::download(new SpecialistExport($items), 'especialistas.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = Specialist::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('Especialista', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }

        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id especialista', $ids);
            }
        }

        $items = $query->orderBy('Id especialista')->get();

        $pdf = PDF::loadView('nomencladores.specialists.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');

        return $pdf->download('especialistas.pdf');
    }

    public function print(Request $request)
    {
        $query = Specialist::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('Especialista', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }

        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id especialista', $ids);
            }
        }

        $items = $query->orderBy('Id especialista')->get();

        return view('nomencladores.specialists.print', [
            'items' => $items,
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
        
        if ($request->filled('activo')) {
            $filtros[] = "Estado: " . ($request->activo == '1' ? 'Activo' : 'Inactivo');
        }
        
        return $filtros;
    }
}