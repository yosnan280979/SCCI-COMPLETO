<?php

namespace App\Http\Controllers;

use App\Models\DITEC;
use App\Models\Pais;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DITECExport;
use PDF;

class DITECController extends Controller
{
    /**
     * Listado con filtros
     */
    public function index(Request $request)
    {
        $query = DITEC::query();

        // Filtros
        if ($request->filled('No DITEC')) {
            $query->where('No DITEC', 'like', '%' . $request->input('No DITEC') . '%');
        }
        if ($request->filled('Producto')) {
            $query->where('Producto', 'like', '%' . $request->input('Producto') . '%');
        }
        if ($request->filled('Fabricante')) {
            $query->where('Fabricante', 'like', '%' . $request->input('Fabricante') . '%');
        }
        if ($request->filled('Id País')) {
            $query->where('Id País', $request->input('Id País'));
        }
        if ($request->filled('Renueva')) {
            $query->where('Renueva', $request->input('Renueva'));
        }
        if ($request->filled('Vencido')) {
            $query->where('Vencido', $request->input('Vencido'));
        }
        if ($request->filled('En renovacion')) {
            $query->where('En renovacion', $request->input('En renovacion'));
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Otorgamiento', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Otorgamiento', '<=', $request->input('fecha_hasta'));
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Ditec');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $ditecs = $query->paginate(20)->appends($request->query());

        $paises = Pais::orderBy('País')->get();

        return view('ditec.index', compact('ditecs', 'paises'));
    }

    public function create()
    {
        $paises = Pais::orderBy('País')->get();
        return view('ditec.create', compact('paises'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'No DITEC'           => 'required|string|max:255',
            'Producto'           => 'nullable|string|max:255',
            'Fabricante'         => 'nullable|string|max:255',
            'Id País'            => 'required|integer|exists:Nomenclador Paises,Id País',
            'Fecha Otorgamiento' => 'nullable|date',
            'Renueva'            => 'nullable|boolean',
            'Vencido'            => 'nullable|boolean',
            'En renovacion'      => 'nullable|boolean',
            'Suministrador'      => 'nullable|string|max:255',
        ]);

        DITEC::create($data);

        return redirect()->route('ditec.index')->with('success', 'DITEC creado correctamente.');
    }

    public function show($id)
    {
        $ditec = DITEC::findOrFail($id);
        return view('ditec.show', compact('ditec'));
    }

    public function edit($id)
    {
        $ditec = DITEC::findOrFail($id);
        $paises = Pais::orderBy('País')->get();
        return view('ditec.edit', compact('ditec', 'paises'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'No DITEC'           => 'required|string|max:255',
            'Producto'           => 'nullable|string|max:255',
            'Fabricante'         => 'nullable|string|max:255',
            'Id País'            => 'required|integer|exists:Nomenclador Paises,Id País',
            'Fecha Otorgamiento' => 'nullable|date',
            'Renueva'            => 'nullable|boolean',
            'Vencido'            => 'nullable|boolean',
            'En renovacion'      => 'nullable|boolean',
            'Suministrador'      => 'nullable|string|max:255',
        ]);

        $ditec = DITEC::findOrFail($id);
        $ditec->update($data);

        return redirect()->route('ditec.index')->with('success', 'DITEC actualizado correctamente.');
    }

    public function destroy($id)
    {
        $ditec = DITEC::findOrFail($id);
        $ditec->delete();

        return redirect()->route('ditec.index')->with('success', 'DITEC eliminado correctamente.');
    }
    
    /**
     * Eliminar múltiples registros
     */
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros DITEC para eliminar.');
        }
        
        $count = DITEC::whereIn('Id Ditec', $ids)->delete();
        
        return redirect()->route('ditec.index')
            ->with('success', "Se eliminaron $count registros DITEC correctamente.");
    }

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = DITEC::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ditec', $ids);
            }
        }
        
        $ditecs = $query->orderBy('Id Ditec')->get();
        
        return Excel::download(new DITECExport($ditecs), 'ditec.xlsx');
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $query = DITEC::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ditec', $ids);
            }
        }
        
        $ditecs = $query->orderBy('Id Ditec')->get();
        
        $pdf = PDF::loadView('ditec.pdf', [
            'ditecs' => $ditecs,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('ditec.pdf');
    }

    /**
     * Imprimir listado
     */
    public function print(Request $request)
    {
        $query = DITEC::query();
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Ditec', $ids);
            }
        }
        
        $ditecs = $query->orderBy('Id Ditec')->get();
        
        return view('ditec.print', [
            'ditecs' => $ditecs,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('No DITEC')) {
            $query->where('No DITEC', 'like', '%' . $request->input('No DITEC') . '%');
        }
        if ($request->filled('Producto')) {
            $query->where('Producto', 'like', '%' . $request->input('Producto') . '%');
        }
        if ($request->filled('Fabricante')) {
            $query->where('Fabricante', 'like', '%' . $request->input('Fabricante') . '%');
        }
        if ($request->filled('Id País')) {
            $query->where('Id País', $request->input('Id País'));
        }
        if ($request->filled('Renueva')) {
            $query->where('Renueva', $request->input('Renueva'));
        }
        if ($request->filled('Vencido')) {
            $query->where('Vencido', $request->input('Vencido'));
        }
        if ($request->filled('En renovacion')) {
            $query->where('En renovacion', $request->input('En renovacion'));
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('Fecha Otorgamiento', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('Fecha Otorgamiento', '<=', $request->input('fecha_hasta'));
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('No DITEC')) {
            $filtros[] = "No DITEC: " . $request->input('No DITEC');
        }
        
        if ($request->filled('Producto')) {
            $filtros[] = "Producto: " . $request->input('Producto');
        }
        
        if ($request->filled('Fabricante')) {
            $filtros[] = "Fabricante: " . $request->input('Fabricante');
        }
        
        if ($request->filled('Id País')) {
            $pais = Pais::find($request->input('Id País'));
            $filtros[] = "País: " . ($pais ? $pais->País : 'Desconocido');
        }
        
        if ($request->filled('Renueva')) {
            $filtros[] = "Renueva: " . ($request->input('Renueva') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('Vencido')) {
            $filtros[] = "Vencido: " . ($request->input('Vencido') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('En renovacion')) {
            $filtros[] = "En renovación: " . ($request->input('En renovacion') == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $filtros[] = "Fecha: " . $request->input('fecha_desde') . " a " . $request->input('fecha_hasta');
        } elseif ($request->filled('fecha_desde')) {
            $filtros[] = "Desde: " . $request->input('fecha_desde');
        } elseif ($request->filled('fecha_hasta')) {
            $filtros[] = "Hasta: " . $request->input('fecha_hasta');
        }
        
        return $filtros;
    }
}