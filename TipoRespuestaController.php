<?php

namespace App\Http\Controllers;

use App\Models\TipoRespuesta;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TipoRespuestaExport;
use PDF;
use Illuminate\Support\Facades\DB;

class TipoRespuestaController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoRespuesta::query();
        
        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('Tipo Respuesta', 'like', "%{$search}%");
        }
        
        // Ordenación con validación de columnas
        $orderBy = $request->get('order_by', 'Id Tipo respuesta');
        
        // Lista blanca de columnas permitidas para ordenar
        $allowedColumns = ['Id Tipo respuesta', 'Tipo Respuesta'];
        
        // Si la columna no está en la lista blanca, usar la columna por defecto
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'Id Tipo respuesta';
        }
        
        $orderDirection = $request->get('order_direction', 'asc');
        $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'asc';
        
        // Ordenar usando DB::raw para manejar espacios en nombres de columnas
        $query->orderBy(DB::raw("`$orderBy`"), $orderDirection);
        
        $tipoRespuestas = $query->paginate(25)->withQueryString();
        
        return view('nomencladores.tipo-respuesta.index', compact('tipoRespuestas'));
    }

    public function create()
    {
        return view('nomencladores.tipo-respuesta.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Tipo Respuesta' => 'required|string|max:50|unique:Nomenclador de Tipos de respuestas,Tipo Respuesta'
        ]);

        TipoRespuesta::create([
            'Tipo Respuesta' => $request->input('Tipo Respuesta')
        ]);

        return redirect()->route('nomencladores.tipo-respuesta.index')
            ->with('success', 'Tipo de respuesta creado correctamente.');
    }

    public function show($id)
    {
        // Buscar usando la clave primaria con espacios
        $tipoRespuesta = TipoRespuesta::where('Id Tipo respuesta', $id)->firstOrFail();
        return view('nomencladores.tipo-respuesta.show', compact('tipoRespuesta'));
    }

    public function edit($id)
    {
        $tipoRespuesta = TipoRespuesta::where('Id Tipo respuesta', $id)->firstOrFail();
        return view('nomencladores.tipo-respuesta.edit', compact('tipoRespuesta'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Tipo Respuesta' => 'required|string|max:50|unique:Nomenclador de Tipos de respuestas,Tipo Respuesta,' . $id . ',Id Tipo respuesta'
        ]);

        $tipoRespuesta = TipoRespuesta::where('Id Tipo respuesta', $id)->firstOrFail();
        $tipoRespuesta->update([
            'Tipo Respuesta' => $request->input('Tipo Respuesta')
        ]);

        return redirect()->route('nomencladores.tipo-respuesta.index')
            ->with('success', 'Tipo de respuesta actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tipoRespuesta = TipoRespuesta::where('Id Tipo respuesta', $id)->firstOrFail();
        $tipoRespuesta->delete();

        return redirect()->route('nomencladores.tipo-respuesta.index')
            ->with('success', 'Tipo de respuesta eliminado correctamente.');
    }
    
    // Eliminar múltiples tipos de respuesta
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|string'
        ]);
        
        // Convertir string separado por comas a array
        $ids = explode(',', $request->selected_ids);
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron tipos de respuesta.');
        }
        
        $count = TipoRespuesta::whereIn('Id Tipo respuesta', $ids)->delete();
        
        return redirect()->route('nomencladores.tipo-respuesta.index')
            ->with('success', "Se eliminaron {$count} tipos de respuesta correctamente.");
    }
    
    public function exportExcel(Request $request)
    {
        $query = TipoRespuesta::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('Tipo Respuesta', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('selected_ids') && !empty($request->selected_ids)) {
            $ids = explode(',', $request->selected_ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo respuesta', $ids);
            }
        }
        
        // Ordenación con validación
        $orderBy = $request->has('order_by') ? $request->order_by : 'Id Tipo respuesta';
        
        // Lista blanca de columnas permitidas
        $allowedColumns = ['Id Tipo respuesta', 'Tipo Respuesta'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'Id Tipo respuesta';
        }
        
        $orderDirection = $request->has('order_direction') ? $request->order_direction : 'asc';
        $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'asc';
        
        // Ordenar usando DB::raw para manejar espacios
        $query->orderBy(DB::raw("`$orderBy`"), $orderDirection);
        
        $tipoRespuestas = $query->get();
        
        return Excel::download(new TipoRespuestaExport($tipoRespuestas), 'tipos_respuesta_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = TipoRespuesta::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('Tipo Respuesta', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('selected_ids') && !empty($request->selected_ids)) {
            $ids = explode(',', $request->selected_ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo respuesta', $ids);
            }
        }
        
        // Ordenación con validación
        $orderBy = $request->has('order_by') ? $request->order_by : 'Id Tipo respuesta';
        
        $allowedColumns = ['Id Tipo respuesta', 'Tipo Respuesta'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'Id Tipo respuesta';
        }
        
        $orderDirection = $request->has('order_direction') ? $request->order_direction : 'asc';
        $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'asc';
        
        $query->orderBy(DB::raw("`$orderBy`"), $orderDirection);
        
        $tipoRespuestas = $query->get();
        
        $pdf = PDF::loadView('nomencladores.tipo-respuesta.pdf', ['tipoRespuestas' => $tipoRespuestas]);
        return $pdf->download('tipos_respuesta_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = TipoRespuesta::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('Tipo Respuesta', 'like', "%{$search}%");
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('selected_ids') && !empty($request->selected_ids)) {
            $ids = explode(',', $request->selected_ids);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Tipo respuesta', $ids);
            }
        }
        
        // Ordenación con validación
        $orderBy = $request->has('order_by') ? $request->order_by : 'Id Tipo respuesta';
        
        $allowedColumns = ['Id Tipo respuesta', 'Tipo Respuesta'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'Id Tipo respuesta';
        }
        
        $orderDirection = $request->has('order_direction') ? $request->order_direction : 'asc';
        $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'asc';
        
        $query->orderBy(DB::raw("`$orderBy`"), $orderDirection);
        
        $tipoRespuestas = $query->get();
        
        return view('nomencladores.tipo-respuesta.print', compact('tipoRespuestas'));
    }
}