<?php

namespace App\Http\Controllers;

use App\Models\NomencladorActualizar;
use Illuminate\Http\Request;
use App\Exports\NomencladorActualizarExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class NomencladorActualizarController extends Controller
{
    /**
     * Listado con filtros, orden y paginación.
     */
    public function index(Request $request)
    {
        // Capturar parámetros de búsqueda y orden
        $search = $request->input('search');
        $orderBy = $request->input('order_by', 'Id Actualizado');
        $orderDirection = $request->input('order_direction', 'asc');

        // Construir query
        $query = NomencladorActualizar::query();

        // Filtro de búsqueda
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('Id Actualizado', 'like', "%{$search}%")
                  ->orWhere('Actualizado', 'like', "%{$search}%");
            });
        }

        // Ordenar
        $query->orderBy($orderBy, $orderDirection);

        // Paginación (20 registros por página)
        $actualizaciones = $query->paginate(20)->withQueryString();

        return view('nomencladores.actualizar.index', compact('actualizaciones'));
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        return view('nomencladores.actualizar.create');
    }

    /**
     * Guardar nuevo registro.
     */
    public function store(Request $request)
    {
        $request->validate([
            'Actualizado' => 'required|string|max:50',
        ]);

        NomencladorActualizar::create([
            'Actualizado' => $request->Actualizado,
        ]);

        return redirect()->route('nomencladores.actualizar.index')
            ->with('success', 'Tipo de actualización creado exitosamente.');
    }

    /**
     * Mostrar un registro específico.
     */
    public function show($id)
    {
        $actualizacion = NomencladorActualizar::findOrFail($id);
        return view('nomencladores.actualizar.show', compact('actualizacion'));
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit($id)
    {
        $actualizacion = NomencladorActualizar::findOrFail($id);
        return view('nomencladores.actualizar.edit', compact('actualizacion'));
    }

    /**
     * Actualizar registro.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'Actualizado' => 'required|string|max:50',
        ]);

        $actualizacion = NomencladorActualizar::findOrFail($id);
        $actualizacion->update([
            'Actualizado' => $request->Actualizado,
        ]);

        return redirect()->route('nomencladores.actualizar.index')
            ->with('success', 'Tipo de actualización actualizado exitosamente.');
    }

    /**
     * Eliminar registro.
     */
    public function destroy($id)
    {
        $actualizacion = NomencladorActualizar::findOrFail($id);
        $actualizacion->delete();
        
        return redirect()->route('nomencladores.actualizar.index')
            ->with('success', 'Tipo de actualización eliminado exitosamente.');
    }

    /**
     * Eliminar múltiples registros seleccionados.
     */
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Nomenclador de Actualizar,Id Actualizado'
        ]);
        
        $ids = $request->selected_ids;
        $count = NomencladorActualizar::whereIn('Id Actualizado', $ids)->delete();

        return redirect()->route('nomencladores.actualizar.index')
            ->with('success', "Se eliminaron $count tipos de actualización exitosamente.");
    }
    
    /**
     * Exportar a Excel.
     */
    public function exportExcel(Request $request)
    {
        $query = NomencladorActualizar::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Actualizado', 'like', "%{$search}%")
                  ->orWhere('Actualizado', 'like', "%{$search}%");
            });
        }
        
        // Ordenar
        if ($request->filled('order_by') && $request->filled('order_direction')) {
            $query->orderBy($request->order_by, $request->order_direction);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Actualizado', $ids);
            }
        }
        
        $actualizaciones = $query->get();
        
        return Excel::download(new NomencladorActualizarExport($actualizaciones), 'actualizaciones.xlsx');
    }
    
    /**
     * Exportar a PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = NomencladorActualizar::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Actualizado', 'like', "%{$search}%")
                  ->orWhere('Actualizado', 'like', "%{$search}%");
            });
        }
        
        // Ordenar
        if ($request->filled('order_by') && $request->filled('order_direction')) {
            $query->orderBy($request->order_by, $request->order_direction);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Actualizado', $ids);
            }
        }
        
        $actualizaciones = $query->get();
        
        $pdf = PDF::loadView('nomencladores.actualizar.pdf', [
            'actualizaciones' => $actualizaciones,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('actualizaciones.pdf');
    }
    
    /**
     * Vista para imprimir.
     */
    public function print(Request $request)
    {
        $query = NomencladorActualizar::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Id Actualizado', 'like', "%{$search}%")
                  ->orWhere('Actualizado', 'like', "%{$search}%");
            });
        }
        
        // Ordenar
        if ($request->filled('order_by') && $request->filled('order_direction')) {
            $query->orderBy($request->order_by, $request->order_direction);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Actualizado', $ids);
            }
        }
        
        $actualizaciones = $query->get();
        
        return view('nomencladores.actualizar.print', [
            'actualizaciones' => $actualizaciones,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Obtener texto de filtros aplicados.
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('order_by')) {
            $orden = "Orden: " . $request->order_by;
            if ($request->filled('order_direction')) {
                $orden .= " (" . ($request->order_direction == 'asc' ? 'Ascendente' : 'Descendente') . ")";
            }
            $filtros[] = $orden;
        }
        
        return $filtros;
    }
}