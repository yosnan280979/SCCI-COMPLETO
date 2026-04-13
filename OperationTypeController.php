<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OperationType;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OperationTypesExport;
use Barryvdh\DomPDF\Facade\Pdf;

class OperationTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationType::query();

        // Filtros
        if ($search = $request->input('search')) {
            $query->where('Id Tipo Operacion', 'like', "%{$search}%")
                  ->orWhere('Tipo Operacion', 'like', "%{$search}%");
        }

        // Ordenamiento
        $orderBy = $request->input('order_by', 'Id Tipo Operacion');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginación
        $operationTypes = $query->paginate(15)->withQueryString();

        // CORRECCIÓN: Usar la ruta correcta de la vista
        return view('nomencladores.operation_types.index', compact('operationTypes'));
    }

    public function create()
    {
        return view('nomencladores.operation_types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Tipo Operacion' => 'required|string|max:255',
        ]);

        OperationType::create($validated);

        return redirect()->route('nomencladores.operation-types.index')
                         ->with('success', 'Tipo de operación creado correctamente.');
    }

    public function show($id)
    {
        $operationType = OperationType::findOrFail($id);
        return view('nomencladores.operation_types.show', compact('operationType'));
    }

    public function edit($id)
    {
        $operationType = OperationType::findOrFail($id);
        return view('nomencladores.operation_types.edit', compact('operationType'));
    }

    public function update(Request $request, $id)
    {
        $operationType = OperationType::findOrFail($id);

        $validated = $request->validate([
            'Tipo Operacion' => 'required|string|max:255',
        ]);

        $operationType->update($validated);

        return redirect()->route('nomencladores.operation-types.index')
                         ->with('success', 'Tipo de operación actualizado correctamente.');
    }

    public function destroy($id)
    {
        $operationType = OperationType::findOrFail($id);
        $operationType->delete();

        return redirect()->route('nomencladores.operation-types.index')
                         ->with('success', 'Tipo de operación eliminado correctamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return redirect()->back()
                ->with('error', 'No se seleccionaron tipos de operación para eliminar.');
        }

        OperationType::whereIn('Id Tipo Operacion', $ids)->delete();

        return redirect()->route('nomencladores.operation-types.index')
                         ->with('success', 'Tipos de operación eliminados correctamente.');
    }

    // Exportaciones
    public function exportExcel(Request $request)
    {
        // DEPURACIÓN: Ver qué datos llegan
        \Log::info('Export Excel Request:', $request->all());
        
        // Convertir string separado por comas a array
        $selected = $this->parseSelectedInput($request);
        
        if (empty($selected)) {
            // Exportar todos los registros visibles (con filtros aplicados)
            $query = OperationType::query();
            
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('Id Tipo Operacion', 'like', "%{$search}%")
                      ->orWhere('Tipo Operacion', 'like', "%{$search}%");
            }
            
            $items = $query->get();
        } else {
            $items = OperationType::whereIn('Id Tipo Operacion', $selected)->get();
        }

        if ($items->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No hay registros para exportar.');
        }

        return Excel::download(new OperationTypesExport($items), 
            'tipos_operacion_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        // DEPURACIÓN: Ver qué datos llegan
        \Log::info('Export PDF Request:', $request->all());
        
        // Convertir string separado por comas a array
        $selected = $this->parseSelectedInput($request);
        
        if (empty($selected)) {
            // Exportar todos los registros visibles (con filtros aplicados)
            $query = OperationType::query();
            
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('Id Tipo Operacion', 'like', "%{$search}%")
                      ->orWhere('Tipo Operacion', 'like', "%{$search}%");
            }
            
            $items = $query->get();
        } else {
            $items = OperationType::whereIn('Id Tipo Operacion', $selected)->get();
        }

        if ($items->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No hay registros para exportar.');
        }

        // CORRECCIÓN: Usar la ruta correcta de la vista PDF
        $pdf = Pdf::loadView('nomencladores.operation_types.pdf', ['items' => $items]);
        return $pdf->download('tipos_operacion_' . date('Y-m-d') . '.pdf');
    }

    public function print(Request $request)
    {
        // DEPURACIÓN: Ver qué datos llegan
        \Log::info('Print Request:', $request->all());
        \Log::info('Print URL: ' . $request->fullUrl());
        
        // Convertir string separado por comas a array
        $selected = $this->parseSelectedInput($request);
        
        if (empty($selected)) {
            // Si no hay selección, mostrar todos los registros
            $items = OperationType::all();
        } else {
            $items = OperationType::whereIn('Id Tipo Operacion', $selected)->get();
        }

        // DEPURACIÓN: Ver qué items se obtienen
        \Log::info('Items count: ' . $items->count());
        
        // CORRECCIÓN: Usar la ruta correcta de la vista de impresión
        return view('nomencladores.operation_types.print', compact('items'));
    }

    /**
     * Parse selected input from string to array
     */
    private function parseSelectedInput(Request $request)
    {
        \Log::info('Parsing selected input:', ['input' => $request->input('selected')]);
        
        $selected = $request->input('selected');
        
        if (is_array($selected)) {
            return $selected;
        }
        
        if (is_string($selected) && !empty($selected)) {
            return explode(',', $selected);
        }
        
        return [];
    }
}