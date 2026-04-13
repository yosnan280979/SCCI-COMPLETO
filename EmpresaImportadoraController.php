<?php

namespace App\Http\Controllers;

use App\Models\EmpresaImportadora;
use App\Models\Ministerio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmpresasImportadorasExport;
use PDF;

class EmpresaImportadoraController extends Controller
{
    public function index(Request $request)
    {
        $query = EmpresaImportadora::with('ministerio');
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Empresa Importadora', 'like', "%{$search}%")
                  ->orWhere('Siglas', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_ministerio')) {
            $query->where('Id Ministerio', $request->id_ministerio);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Emp Imp');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $empresas = $query->paginate(20)->appends($request->query());
        $ministerios = Ministerio::all();
        
        return view('nomencladores.importing-companies.index', compact('empresas', 'ministerios'));
    }

    public function create()
    {
        $ministerios = Ministerio::all();
        return view('nomencladores.importing-companies.create', compact('ministerios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Empresa Importadora' => 'required|string|max:150|unique:Nomenclador de Empresas Importadoras,Empresa Importadora',
            'Siglas' => 'required|string|max:50',
            'Id Ministerio' => 'required|exists:Nomenclador de Ministerios,Id Ministerio'
        ]);

        EmpresaImportadora::create($request->all());
        return redirect()->route('nomencladores.importing-companies.index')
            ->with('success', 'Empresa Importadora creada correctamente');
    }

    public function show($id)
    {
        $empresa = EmpresaImportadora::with('ministerio')->findOrFail($id);
        return view('nomencladores.importing-companies.show', compact('empresa'));
    }

    public function edit($id)
    {
        $empresa = EmpresaImportadora::findOrFail($id);
        $ministerios = Ministerio::all();
        return view('nomencladores.importing-companies.edit', compact('empresa', 'ministerios'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Empresa Importadora' => 'required|string|max:150|unique:Nomenclador de Empresas Importadoras,Empresa Importadora,' . $id . ',Id Emp Imp',
            'Siglas' => 'required|string|max:50',
            'Id Ministerio' => 'required|exists:Nomenclador de Ministerios,Id Ministerio'
        ]);

        $empresa = EmpresaImportadora::findOrFail($id);
        $empresa->update($request->all());

        return redirect()->route('nomencladores.importing-companies.index')
            ->with('success', 'Empresa Importadora actualizada correctamente');
    }

    public function destroy($id)
    {
        $empresa = EmpresaImportadora::findOrFail($id);
        $empresa->delete();

        return redirect()->route('nomencladores.importing-companies.index')
            ->with('success', 'Empresa Importadora eliminada correctamente');
    }
    
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron empresas importadoras para eliminar.');
        }
        
        $count = EmpresaImportadora::whereIn('Id Emp Imp', $ids)->delete();
        
        return redirect()->route('nomencladores.importing-companies.index')
            ->with('success', "Se eliminaron {$count} empresas importadoras correctamente");
    }
    
    public function exportExcel(Request $request)
    {
        $query = EmpresaImportadora::with('ministerio');
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros - CORREGIDO
        if ($request->filled('selected_ids')) {
            $selected = $request->input('selected_ids');
            if (!empty($selected)) {
                $ids = explode(',', $selected);
                $ids = array_filter($ids);
                if (!empty($ids)) {
                    $query->whereIn('Id Emp Imp', $ids);
                }
            }
        }
        
        $empresas = $query->orderBy('Id Emp Imp')->get();
        
        return Excel::download(new EmpresasImportadorasExport($empresas), 'empresas_importadoras.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = EmpresaImportadora::with('ministerio');
        
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros - CORREGIDO
        if ($request->filled('selected_ids')) {
            $selected = $request->input('selected_ids');
            if (!empty($selected)) {
                $ids = explode(',', $selected);
                $ids = array_filter($ids);
                if (!empty($ids)) {
                    $query->whereIn('Id Emp Imp', $ids);
                }
            }
        }
        
        $empresas = $query->orderBy('Id Emp Imp')->get();
        
        $pdf = PDF::loadView('nomencladores.importing-companies.pdf', [
            'data' => $empresas, // Cambiado a 'data' para evitar conflictos
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('empresas_importadoras.pdf');
    }
    
    public function print(Request $request)
    {
        $query = EmpresaImportadora::with('ministerio');
        
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros - CORREGIDO
        if ($request->filled('selected_ids')) {
            $selected = $request->input('selected_ids');
            if (!empty($selected)) {
                $ids = explode(',', $selected);
                $ids = array_filter($ids);
                if (!empty($ids)) {
                    $query->whereIn('Id Emp Imp', $ids);
                }
            }
        }
        
        $empresas = $query->orderBy('Id Emp Imp')->get();
        
        return view('nomencladores.importing-companies.print', [
            'data' => $empresas, // Cambiado a 'data' para evitar conflictos
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('Empresa Importadora', 'like', "%{$search}%")
                  ->orWhere('Siglas', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('id_ministerio')) {
            $query->where('Id Ministerio', $request->id_ministerio);
        }
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('id_ministerio')) {
            $ministerio = Ministerio::find($request->id_ministerio);
            $filtros[] = "Ministerio: " . ($ministerio ? $ministerio->Ministerio : $request->id_ministerio);
        }
        
        // Agregar información sobre selección
        if ($request->filled('selected_ids')) {
            $selected = $request->input('selected_ids');
            if (!empty($selected)) {
                $ids = explode(',', $selected);
                $count = count(array_filter($ids));
                if ($count > 0) {
                    $filtros[] = "Seleccionados: {$count} registro(s) específico(s)";
                }
            }
        }
        
        return $filtros;
    }
}