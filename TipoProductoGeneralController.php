<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoProductoGeneral;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TipoProductosGeneralExport;
use PDF;

class TipoProductoGeneralController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:lector');
    }
    
    public function index(Request $request)
    {
        $query = TipoProductoGeneral::query();
        
        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Prod general', 'like', "%{$search}%")
                  ->orWhere('Grupo', 'like', "%{$search}%");
            });
        }
        
        // Ordenación - con validación
        $orderBy = $request->get('order_by', 'IdTipoprodg');
        $orderDirection = $request->get('order_direction', 'desc');
        
        // Validar que order_direction sea 'asc' o 'desc'
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'desc';
        }
        
        // Validar que order_by sea una columna válida
        $validColumns = ['IdTipoprodg', 'Tipo Prod general', 'Grupo', 'Arancel CUC', 'Arancel CUP'];
        if (!in_array($orderBy, $validColumns)) {
            $orderBy = 'IdTipoprodg';
        }
        
        $query->orderBy($orderBy, $orderDirection);
        
        $tipoProductosGenerales = $query->paginate(25)->withQueryString();
        
        return view('nomencladores.tipo_productos_general.index', compact('tipoProductosGenerales'));
    }
    
    public function create()
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para crear tipos de producto general.');
        }
        
        return view('nomencladores.tipo_productos_general.create');
    }
    
    public function store(Request $request)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para crear tipos de producto general.');
        }
        
        $request->validate([
            'Tipo Prod general' => 'required|string|max:50|unique:Tipo Producto General,Tipo Prod general',
            'Grupo' => 'nullable|string|max:10',
            'Arancel CUC' => 'nullable|numeric|min:0',
            'Arancel CUP' => 'nullable|numeric|min:0',
        ]);
        
        try {
            TipoProductoGeneral::create($request->all());
            return redirect()->route('nomencladores.tipo_productos_general.index')
                ->with('success', 'Tipo de Producto General creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear tipo de producto general: ' . $e->getMessage());
        }
    }
    
    public function show($id)
    {
        $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
        
        return view('nomencladores.tipo_productos_general.show', compact('tipoProductoGeneral'));
    }
    
    public function edit($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para editar tipos de producto general.');
        }
        
        $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
        
        return view('nomencladores.tipo_productos_general.edit', compact('tipoProductoGeneral'));
    }
    
    public function update(Request $request, $id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para editar tipos de producto general.');
        }
        
        $request->validate([
            'Tipo Prod general' => 'required|string|max:50|unique:Tipo Producto General,Tipo Prod general,' . $id . ',IdTipoprodg',
            'Grupo' => 'nullable|string|max:10',
            'Arancel CUC' => 'nullable|numeric|min:0',
            'Arancel CUP' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
            $tipoProductoGeneral->update($request->all());
            
            return redirect()->route('nomencladores.tipo_productos_general.show', $tipoProductoGeneral->IdTipoprodg)
                ->with('success', 'Tipo de Producto General actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar tipo de producto general: ' . $e->getMessage());
        }
    }
    
    public function destroy($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} != 1) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'Solo los administradores pueden eliminar tipos de producto general.');
        }
        
        try {
            $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
            
            // Verificar si hay solicitudes relacionadas
            if ($tipoProductoGeneral->solicitudes()->count() > 0) {
                return redirect()->route('nomencladores.tipo_productos_general.index')
                    ->with('error', 'No se puede eliminar el tipo de producto general porque tiene solicitudes asociadas.');
            }
            
            $tipoProductoGeneral->delete();
            
            return redirect()->route('nomencladores.tipo_productos_general.index')
                ->with('success', 'Tipo de Producto General eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('nomencladores.tipo_productos_general.index')
                ->with('error', 'Error al eliminar tipo de producto general: ' . $e->getMessage());
        }
    }
    
    // Eliminar múltiples tipos de producto general
    public function destroyMultiple(Request $request)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} != 1) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'Solo los administradores pueden eliminar tipos de producto general.');
        }
        
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Tipo Producto General,IdTipoprodg'
        ]);
        
        try {
            $count = 0;
            foreach ($request->ids as $id) {
                $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
                // Verificar si tiene solicitudes relacionadas
                if ($tipoProductoGeneral->solicitudes()->count() == 0) {
                    $tipoProductoGeneral->delete();
                    $count++;
                }
            }
            
            return redirect()->route('nomencladores.tipo_productos_general.index')
                ->with('success', "{$count} tipos de producto general eliminados correctamente.");
        } catch (\Exception $e) {
            return redirect()->route('nomencladores.tipo_productos_general.index')
                ->with('error', 'Error al eliminar tipos de producto general: ' . $e->getMessage());
        }
    }
    
    public function exportExcel(Request $request)
    {
        $query = TipoProductoGeneral::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Prod general', 'like', "%{$search}%")
                  ->orWhere('Grupo', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('selected') && !empty($request->selected)) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('IdTipoprodg', $ids);
            }
        }
        
        // Ordenación con validación
        if ($request->has('order_by')) {
            $orderBy = $request->order_by;
            $orderDirection = $request->get('order_direction', 'desc');
            
            // Validar order_direction
            if (!in_array($orderDirection, ['asc', 'desc'])) {
                $orderDirection = 'desc';
            }
            
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('IdTipoprodg', 'desc');
        }
        
        $tipoProductosGenerales = $query->get();
        
        return Excel::download(new TipoProductosGeneralExport($tipoProductosGenerales), 'tipo_productos_general_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = TipoProductoGeneral::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Prod general', 'like', "%{$search}%")
                  ->orWhere('Grupo', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('selected') && !empty($request->selected)) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('IdTipoprodg', $ids);
            }
        }
        
        // Ordenación con validación
        if ($request->has('order_by')) {
            $orderBy = $request->order_by;
            $orderDirection = $request->get('order_direction', 'desc');
            
            // Validar order_direction
            if (!in_array($orderDirection, ['asc', 'desc'])) {
                $orderDirection = 'desc';
            }
            
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('IdTipoprodg', 'desc');
        }
        
        $tipoProductosGenerales = $query->get();
        
        $pdf = PDF::loadView('nomencladores.tipo_productos_general.pdf', compact('tipoProductosGenerales'))
            ->setPaper('a4', 'portrait');
        
        return $pdf->download('tipo_productos_general_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $query = TipoProductoGeneral::query();
        
        // Aplicar los mismos filtros que en el index
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Tipo Prod general', 'like', "%{$search}%")
                  ->orWhere('Grupo', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por IDs seleccionados
        if ($request->has('selected') && !empty($request->selected)) {
            $ids = explode(',', $request->selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('IdTipoprodg', $ids);
            }
        }
        
        // Ordenación con validación
        if ($request->has('order_by')) {
            $orderBy = $request->order_by;
            $orderDirection = $request->get('order_direction', 'desc');
            
            // Validar order_direction
            if (!in_array($orderDirection, ['asc', 'desc'])) {
                $orderDirection = 'desc';
            }
            
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('IdTipoprodg', 'desc');
        }
        
        $tipoProductosGenerales = $query->get();
        
        return view('nomencladores.tipo_productos_general.print', compact('tipoProductosGenerales'));
    }
}