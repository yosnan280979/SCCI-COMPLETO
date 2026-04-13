<?php

namespace App\Http\Controllers;

use App\Models\ProveedorSalirMercado;
use App\Models\Provider;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorSalirMercadoExport;
use PDF;

class ProveedorSalirMercadoController extends Controller
{
    public function index(Request $request)
    {
        // Construir la consulta con filtros
        $query = ProveedorSalirMercado::with('datosProveedor');

        // Filtro de búsqueda general
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('Usuario', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('Proveedor', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('No Solicitud', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('Tipo Producto', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filtros específicos
        if ($request->filled('Usuario')) {
            $query->where('Usuario', 'LIKE', '%' . $request->input('Usuario') . '%');
        }
        if ($request->filled('No_Solicitud')) {
            $query->where('No Solicitud', 'LIKE', '%' . $request->input('No_Solicitud') . '%');
        }
        if ($request->filled('Tipo_Producto')) {
            $query->where('Tipo Producto', 'LIKE', '%' . $request->input('Tipo_Producto') . '%');
        }
        if ($request->filled('Proveedor')) {
            $query->where('Proveedor', 'LIKE', '%' . $request->input('Proveedor') . '%');
        }
        if ($request->filled('Momento')) {
            $query->whereDate('Momento', $request->input('Momento'));
        }

        // Ordenamiento - Validar que sort_dir sea 'asc' o 'desc'
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = strtolower($request->input('sort_dir', 'desc'));
        
        // Validar dirección de ordenación
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }
        
        // Mapear nombres de parámetros a nombres reales de columnas
        $columnMap = [
            'Id' => 'Id',
            'Usuario' => 'Usuario',
            'No_Solicitud' => 'No Solicitud',
            'Tipo_Producto' => 'Tipo Producto',
            'Proveedor' => 'Proveedor',
            'Momento' => 'Momento'
        ];
        
        $realSortBy = isset($columnMap[$sortBy]) ? $columnMap[$sortBy] : 'Id';
        
        // Ordenar por la columna real
        $query->orderBy($realSortBy, $sortDir);

        // Paginación con preservación de parámetros
        $items = $query->paginate(20)->appends($request->query());

        return view('proveedor-salir-mercado.index', compact('items'));
    }

    public function create()
    {
        return view('proveedor-salir-mercado.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Usuario' => 'required|string|max:255',
            'No_Solicitud' => 'required|string|max:255',
            'Tipo_Producto' => 'required|string|max:255',
            'Proveedor' => 'required|string|max:255',
            'Momento' => 'nullable|date',
        ]);
        
        // Mapear nombres de request a nombres de columnas reales
        $data = [
            'Usuario' => $validated['Usuario'],
            'No Solicitud' => $validated['No_Solicitud'],
            'Tipo Producto' => $validated['Tipo_Producto'],
            'Proveedor' => $validated['Proveedor'],
            'Momento' => $validated['Momento'] ?? null,
        ];

        ProveedorSalirMercado::create($data);
        
        return redirect()->route('proveedor-salir-mercado.index')
            ->with('success', 'Proveedor para salir al mercado creado exitosamente.');
    }

    public function show($id)
    {
        $item = ProveedorSalirMercado::findOrFail($id);
        return view('proveedor-salir-mercado.show', compact('item'));
    }

    public function edit($id)
    {
        $item = ProveedorSalirMercado::findOrFail($id);
        return view('proveedor-salir-mercado.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ProveedorSalirMercado::findOrFail($id);
        
        $validated = $request->validate([
            'Usuario' => 'required|string|max:255',
            'No_Solicitud' => 'required|string|max:255',
            'Tipo_Producto' => 'required|string|max:255',
            'Proveedor' => 'required|string|max:255',
            'Momento' => 'nullable|date',
        ]);
        
        // Mapear nombres de request a nombres de columnas reales
        $data = [
            'Usuario' => $validated['Usuario'],
            'No Solicitud' => $validated['No_Solicitud'],
            'Tipo Producto' => $validated['Tipo_Producto'],
            'Proveedor' => $validated['Proveedor'],
            'Momento' => $validated['Momento'] ?? null,
        ];

        $item->update($data);
        
        return redirect()->route('proveedor-salir-mercado.index')
            ->with('success', 'Proveedor para salir al mercado actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $item = ProveedorSalirMercado::findOrFail($id);
        $item->delete();
        
        return redirect()->route('proveedor-salir-mercado.index')
            ->with('success', 'Registro eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros para eliminar.');
        }
        
        $count = ProveedorSalirMercado::whereIn('Id', $ids)->delete();
        
        return redirect()->route('proveedor-salir-mercado.index')
            ->with('success', "Se eliminaron $count registros correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = ProveedorSalirMercado::with('datosProveedor');
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        return Excel::download(new ProveedorSalirMercadoExport($items), 'proveedor-salir-mercado.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = ProveedorSalirMercado::with('datosProveedor');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        // Configurar PDF en orientación horizontal
        $pdf = PDF::loadView('proveedor-salir-mercado.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('proveedor-salir-mercado.pdf');
    }

    public function print(Request $request)
    {
        $query = ProveedorSalirMercado::with('datosProveedor');
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->orderBy('Id')->get();
        
        return view('proveedor-salir-mercado.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        // Filtro de búsqueda general
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('Usuario', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('Proveedor', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('No Solicitud', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('Tipo Producto', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filtros específicos
        if ($request->filled('Usuario')) {
            $query->where('Usuario', 'LIKE', '%' . $request->input('Usuario') . '%');
        }
        if ($request->filled('No_Solicitud')) {
            $query->where('No Solicitud', 'LIKE', '%' . $request->input('No_Solicitud') . '%');
        }
        if ($request->filled('Tipo_Producto')) {
            $query->where('Tipo Producto', 'LIKE', '%' . $request->input('Tipo_Producto') . '%');
        }
        if ($request->filled('Proveedor')) {
            $query->where('Proveedor', 'LIKE', '%' . $request->input('Proveedor') . '%');
        }
        if ($request->filled('Momento')) {
            $query->whereDate('Momento', $request->input('Momento'));
        }

        // Ordenamiento - Validar dirección
        $sortBy = $request->input('sort_by', 'Id');
        $sortDir = strtolower($request->input('sort_dir', 'desc'));
        
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }
        
        $columnMap = [
            'Id' => 'Id',
            'Usuario' => 'Usuario',
            'No_Solicitud' => 'No Solicitud',
            'Tipo_Producto' => 'Tipo Producto',
            'Proveedor' => 'Proveedor',
            'Momento' => 'Momento'
        ];
        
        $realSortBy = isset($columnMap[$sortBy]) ? $columnMap[$sortBy] : 'Id';
        
        $query->orderBy($realSortBy, $sortDir);
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->input('search');
        }
        if ($request->filled('Usuario')) {
            $filtros[] = "Usuario: " . $request->input('Usuario');
        }
        if ($request->filled('No_Solicitud')) {
            $filtros[] = "No. Solicitud: " . $request->input('No_Solicitud');
        }
        if ($request->filled('Tipo_Producto')) {
            $filtros[] = "Tipo Producto: " . $request->input('Tipo_Producto');
        }
        if ($request->filled('Proveedor')) {
            $filtros[] = "Proveedor: " . $request->input('Proveedor');
        }
        if ($request->filled('Momento')) {
            $filtros[] = "Momento: " . $request->input('Momento');
        }

        return $filtros;
    }
}