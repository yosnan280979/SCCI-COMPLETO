<?php

namespace App\Http\Controllers;

use App\Models\ProveedorEmpImp;
use App\Models\Provider;
use App\Models\EmpresaImportadora;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorEmpImpExport;
use PDF;

class ProveedorEmpImpController extends Controller
{
    public function index(Request $request)
    {
        $query = ProveedorEmpImp::with(['proveedor', 'empresaImportadora']);
        
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('proveedor', function($q2) use ($search) {
                    $q2->where('Proveedor', 'like', "%{$search}%");
                })->orWhereHas('empresaImportadora', function($q2) use ($search) {
                    $q2->where('Empresa Importadora', 'like', "%{$search}%");
                });
            });
        }
        
        // Filtro por proveedor
        if ($request->filled('id_proveedor')) {
            $query->where('Id_Proveedor', $request->id_proveedor);
        }
        
        // Filtro por empresa importadora
        if ($request->filled('id_empresa')) {
            $query->where('Id_EmpresaImportadora', $request->id_empresa);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        
        // Si es una columna de relación, necesitamos usar join
        if ($sortBy === 'Proveedor') {
            $query->join('Nomenclador Proveedores', 'Proveedor_Emp_Imp.Id_Proveedor', '=', 'Nomenclador Proveedores.Id Proveedor')
                  ->orderBy('Nomenclador Proveedores.Proveedor', $sortDir)
                  ->select('Proveedor_Emp_Imp.*');
        } elseif ($sortBy === 'Empresa Importadora') {
            $query->join('Nomenclador de Empresas Importadoras', 'Proveedor_Emp_Imp.Id_EmpresaImportadora', '=', 'Nomenclador de Empresas Importadoras.Id Emp Imp')
                  ->orderBy('Nomenclador de Empresas Importadoras.Empresa Importadora', $sortDir)
                  ->select('Proveedor_Emp_Imp.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
        
        // Paginación con parámetros preservados
        $proveedorEmpImps = $query->paginate(20)->appends($request->query());
        $proveedores = Provider::orderBy('Proveedor')->get();
        $empresasImportadoras = EmpresaImportadora::orderBy('Empresa Importadora')->get();
        
        return view('proveedor_emp_imp.index', compact('proveedorEmpImps', 'proveedores', 'empresasImportadoras'));
    }

    public function create()
    {
        $proveedores = Provider::orderBy('Proveedor')->get();
        $empresasImportadoras = EmpresaImportadora::orderBy('Empresa Importadora')->get();
        return view('proveedor_emp_imp.create', compact('proveedores', 'empresasImportadoras'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Id_EmpresaImportadora' => 'required|exists:Nomenclador de Empresas Importadoras,Id Emp Imp',
        ]);
        
        ProveedorEmpImp::create($validated);
        
        return redirect()->route('proveedor-emp-imp.index')
            ->with('success', 'Relación creada exitosamente.');
    }

    public function show($id)
    {
        $relacion = ProveedorEmpImp::with(['proveedor', 'empresaImportadora'])->findOrFail($id);
        return view('proveedor_emp_imp.show', compact('relacion'));
    }

    public function edit($id)
    {
        $relacion = ProveedorEmpImp::findOrFail($id);
        $proveedores = Provider::orderBy('Proveedor')->get();
        $empresasImportadoras = EmpresaImportadora::orderBy('Empresa Importadora')->get();
        return view('proveedor_emp_imp.edit', compact('relacion', 'proveedores', 'empresasImportadoras'));
    }

    public function update(Request $request, $id)
    {
        $relacion = ProveedorEmpImp::findOrFail($id);
        
        $validated = $request->validate([
            'Id_Proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Id_EmpresaImportadora' => 'required|exists:Nomenclador de Empresas Importadoras,Id Emp Imp',
        ]);
        
        $relacion->update($validated);
        
        return redirect()->route('proveedor-emp-imp.index')
            ->with('success', 'Relación actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $relacion = ProveedorEmpImp::findOrFail($id);
        $relacion->delete();
        
        return redirect()->route('proveedor-emp-imp.index')
            ->with('success', 'Relación eliminada exitosamente.');
    }
    
    // Eliminar múltiples relaciones
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron relaciones para eliminar.');
        }
        
        $count = ProveedorEmpImp::whereIn('Id', $ids)->delete();
        
        return redirect()->route('proveedor-emp-imp.index')
            ->with('success', "Se eliminaron $count relaciones correctamente.");
    }
    
    // Exportaciones
    public function exportExcel(Request $request)
    {
        $query = ProveedorEmpImp::with(['proveedor', 'empresaImportadora']);
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección
        $selected = $request->input('selected_ids', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $relaciones = $query->orderBy('Id')->get();
        
        return Excel::download(new ProveedorEmpImpExport($relaciones), 'relaciones-proveedor-empresa.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = ProveedorEmpImp::with(['proveedor', 'empresaImportadora']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $relaciones = $query->orderBy('Id')->get();
        
        // Configurar PDF en orientación horizontal
        $pdf = PDF::loadView('proveedor_emp_imp.pdf', [
            'relaciones' => $relaciones,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('relaciones-proveedor-empresa.pdf');
    }
    
    public function print(Request $request)
    {
        $query = ProveedorEmpImp::with(['proveedor', 'empresaImportadora']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $relaciones = $query->orderBy('Id')->get();
        
        return view('proveedor_emp_imp.print', [
            'relaciones' => $relaciones,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Aplicar filtros comunes
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('proveedor', function($q2) use ($search) {
                    $q2->where('Proveedor', 'like', "%{$search}%");
                })->orWhereHas('empresaImportadora', function($q2) use ($search) {
                    $q2->where('Empresa Importadora', 'like', "%{$search}%");
                });
            });
        }
        
        if ($request->filled('id_proveedor')) {
            $query->where('Id_Proveedor', $request->id_proveedor);
        }
        
        if ($request->filled('id_empresa')) {
            $query->where('Id_EmpresaImportadora', $request->id_empresa);
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
        
        if ($request->filled('id_proveedor')) {
            $proveedor = Provider::find($request->id_proveedor);
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor . " (ID: {$request->id_proveedor})" : $request->id_proveedor);
        }
        
        if ($request->filled('id_empresa')) {
            $empresa = EmpresaImportadora::find($request->id_empresa);
            $filtros[] = "Empresa Importadora: " . ($empresa ? $empresa->{'Empresa Importadora'} . " (ID: {$request->id_empresa})" : $request->id_empresa);
        }
        
        return $filtros;
    }
}