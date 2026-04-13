<?php

namespace App\Http\Controllers;

use App\Models\DatosPersonalExtranjero;
use App\Models\Provider;
use Illuminate\Http\Request;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PersonalExtranjeroExport;

class DatosPersonalExtranjeroController extends Controller
{
    public function index(Request $request)
    {
        $query = DatosPersonalExtranjero::with('proveedor');

        // Filtros
        if ($request->filled('Id_proveedor')) {
            $query->where('Id_proveedor', $request->Id_proveedor);
        }

        if ($request->filled('Funcionario_extranjero')) {
            $query->where('Funcionario_extranjero', 'like', '%' . $request->Funcionario_extranjero . '%');
        }

        if ($request->filled('Cargo')) {
            $query->where('Cargo', 'like', '%' . $request->Cargo . '%');
        }

        if ($request->filled('Email')) {
            $query->where('Email', 'like', '%' . $request->Email . '%');
        }

        if ($request->filled('Telef')) {
            $query->where('Telef', 'like', '%' . $request->Telef . '%');
        }

        if ($request->filled('Activo') && in_array($request->Activo, ['0', '1'])) {
            $query->where('Activo', $request->Activo);
        }

        if ($request->filled('Firmante') && in_array($request->Firmante, ['0', '1'])) {
            $query->where('Firmante', $request->Firmante);
        }
        
        if ($request->filled('Permiso_de_Trabajo')) {
            $query->where('Permiso_de_Trabajo', 'like', '%' . $request->Permiso_de_Trabajo . '%');
        }
        
        if ($request->filled('Id_pais')) {
            $query->where('Id_pais', $request->Id_pais);
        }

        // Ordenación - CORREGIDO: usando los mismos nombres de parámetros que la vista
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $items = $query->paginate(25)->appends($request->query());
        $proveedores = Provider::orderBy('Proveedor')->get();

        return view('datos-personal-extranjero.index', compact('items', 'proveedores'));
    }

    public function create()
    {
        $proveedores = Provider::orderBy('Proveedor')->get();
        return view('datos-personal-extranjero.create', compact('proveedores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Funcionario_extranjero' => 'required|string|max:50',
            'Telef' => 'nullable|string|max:50',
            'Email' => 'nullable|email|max:50',
            'Activo' => 'required|boolean',
            'Cargo' => 'nullable|string|max:50',
            'Permiso_de_Trabajo' => 'nullable|string|max:50',
            'Id_pais' => 'nullable|integer',
            'Fecha_Vencimiento' => 'nullable|date',
            'Firmante' => 'required|boolean',
            'Escnot' => 'nullable|string|max:50',
            'Aprot' => 'nullable|integer',
            'Fechavenpod' => 'nullable|date',
        ]);

        DatosPersonalExtranjero::create($validated);
        
        return redirect()->route('datos-personal-extranjero.index')
            ->with('success', 'Personal extranjero creado exitosamente.');
    }

    public function show($id)
    {
        $item = DatosPersonalExtranjero::with('proveedor')->findOrFail($id);
        return view('datos-personal-extranjero.show', compact('item'));
    }

    public function edit($id)
    {
        $item = DatosPersonalExtranjero::findOrFail($id);
        $proveedores = Provider::orderBy('Proveedor')->get();
        return view('datos-personal-extranjero.edit', compact('item', 'proveedores'));
    }

    public function update(Request $request, $id)
    {
        $item = DatosPersonalExtranjero::findOrFail($id);
        
        $validated = $request->validate([
            'Id_proveedor' => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Funcionario_extranjero' => 'required|string|max:50',
            'Telef' => 'nullable|string|max:50',
            'Email' => 'nullable|email|max:50',
            'Activo' => 'required|boolean',
            'Cargo' => 'nullable|string|max:50',
            'Permiso_de_Trabajo' => 'nullable|string|max:50',
            'Id_pais' => 'nullable|integer',
            'Fecha_Vencimiento' => 'nullable|date',
            'Firmante' => 'required|boolean',
            'Escnot' => 'nullable|string|max:50',
            'Aprot' => 'nullable|integer',
            'Fechavenpod' => 'nullable|date',
        ]);

        $item->update($validated);
        
        return redirect()->route('datos-personal-extranjero.index')
            ->with('success', 'Personal extranjero actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $item = DatosPersonalExtranjero::findOrFail($id);
        $item->delete();
        
        return redirect()->route('datos-personal-extranjero.index')
            ->with('success', 'Personal extranjero eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:Datos Personal Extranjero,Id'
        ]);
        
        $ids = $request->ids;
        $count = DatosPersonalExtranjero::whereIn('Id', $ids)->delete();
        
        return redirect()->route('datos-personal-extranjero.index')
            ->with('success', "Se eliminaron $count registros correctamente.");
    }

    // Métodos de exportación - ELIMINADO @csrf
    public function exportExcel(Request $request)
    {
        $query = DatosPersonalExtranjero::with('proveedor');

        // Aplicar filtros desde el request
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }

        $items = $query->get();
        
        return Excel::download(new PersonalExtranjeroExport($items), 'personal-extranjero-' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = DatosPersonalExtranjero::with('proveedor');

        // Aplicar filtros desde el request
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }

        $items = $query->get();
        
        $filtros = $this->obtenerFiltrosTexto($request);

        $pdf = PDF::loadView('datos-personal-extranjero.pdf', compact('items', 'filtros'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'sans-serif']);
        
        return $pdf->download('personal-extranjero-' . date('Y-m-d') . '.pdf');
    }

    public function print(Request $request)
    {
        $query = DatosPersonalExtranjero::with('proveedor');

        // Aplicar filtros desde el request
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }

        $items = $query->get();
        
        $filtros = $this->obtenerFiltrosTexto($request);

        return view('datos-personal-extranjero.print', compact('items', 'filtros'));
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('Id_proveedor')) {
            $query->where('Id_proveedor', $request->Id_proveedor);
        }

        if ($request->filled('Funcionario_extranjero')) {
            $query->where('Funcionario_extranjero', 'like', '%' . $request->Funcionario_extranjero . '%');
        }

        if ($request->filled('Cargo')) {
            $query->where('Cargo', 'like', '%' . $request->Cargo . '%');
        }

        if ($request->filled('Email')) {
            $query->where('Email', 'like', '%' . $request->Email . '%');
        }

        if ($request->filled('Telef')) {
            $query->where('Telef', 'like', '%' . $request->Telef . '%');
        }

        if ($request->filled('Activo') && in_array($request->Activo, ['0', '1'])) {
            $query->where('Activo', $request->Activo);
        }

        if ($request->filled('Firmante') && in_array($request->Firmante, ['0', '1'])) {
            $query->where('Firmante', $request->Firmante);
        }
        
        if ($request->filled('Permiso_de_Trabajo')) {
            $query->where('Permiso_de_Trabajo', 'like', '%' . $request->Permiso_de_Trabajo . '%');
        }
        
        if ($request->filled('Id_pais')) {
            $query->where('Id_pais', $request->Id_pais);
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($request->input('sort_by'), $sortDir);
        } else {
            $query->orderBy('Id', 'desc');
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltrosTexto(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('Funcionario_extranjero')) {
            $filtros[] = "Funcionario: " . $request->Funcionario_extranjero;
        }
        
        if ($request->filled('Cargo')) {
            $filtros[] = "Cargo: " . $request->Cargo;
        }
        
        if ($request->filled('Email')) {
            $filtros[] = "Email: " . $request->Email;
        }
        
        if ($request->filled('Telef')) {
            $filtros[] = "Teléfono: " . $request->Telef;
        }
        
        if ($request->filled('Activo')) {
            $filtros[] = "Activo: " . ($request->Activo == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('Firmante')) {
            $filtros[] = "Firmante: " . ($request->Firmante == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('Id_proveedor')) {
            $proveedor = Provider::find($request->Id_proveedor);
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor : 'Desconocido');
        }
        
        if ($request->filled('Permiso_de_Trabajo')) {
            $filtros[] = "Permiso de Trabajo: " . $request->Permiso_de_Trabajo;
        }
        
        if ($request->filled('Id_pais')) {
            $filtros[] = "ID País: " . $request->Id_pais;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}