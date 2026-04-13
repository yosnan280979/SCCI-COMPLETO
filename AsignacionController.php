<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\BalanceCenter;
use App\Models\CreditLine;
use App\Models\FinancingSource;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsignacionesExport;
use PDF;
use Illuminate\Support\Facades\DB;

class AsignacionController extends Controller
{
    public function index(Request $request)
    {
        // Consulta con relaciones cargadas
        $query = Asignacion::query();
        
        // Cargar relaciones
        $query->with(['centroBalance', 'lineaCredito', 'fuenteFinanciamiento']);
        
        // Filtro de búsqueda general - AGREGADO
        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('centroBalance', function($q) use ($search) {
                    $q->where('Centro Balance', 'like', "%{$search}%");
                })
                ->orWhereHas('lineaCredito', function($q) use ($search) {
                    $q->where('Linea de Crédito', 'like', "%{$search}%");
                })
                ->orWhereHas('fuenteFinanciamiento', function($q) use ($search) {
                    $q->where('Fuente Financiamiento', 'like', "%{$search}%");
                })
                ->orWhere('Año Asignacion', 'like', "%{$search}%");
            });
        }
        
        // Filtro por línea de crédito
        if (!empty($request->Id_Lineacredito)) {
            $query->where('Id Lineacredito', $request->Id_Lineacredito);
        }
        
        // Filtro por fuente de financiamiento
        if (!empty($request->Id_Fuentafinan)) {
            $query->where('Id Fuentafinan', $request->Id_Fuentafinan);
        }
        
        // Filtro por año
        if (!empty($request->Año_Asignacion)) {
            $query->where('Año Asignacion', $request->Año_Asignacion);
        }
        
        // Filtro por centro de balance
        if (!empty($request->Id_Centro_Balance)) {
            $query->where('Id Centro Balance', $request->Id_Centro_Balance);
        }
        
        // Ordenación - CORREGIDO: usando los mismos nombres de parámetros que la vista
        $sortBy = $request->input('sort_by', 'Año Asignacion');
        $sortDir = $request->input('sort_dir', 'desc');
        
        // Mapear columnas de ordenación
        $sortColumnMap = [
            'Centro Balance' => 'Id Centro Balance',
            'Linea Credito' => 'Id Lineacredito',
            'Fuente Financiamiento' => 'Id Fuentafinan',
            'Año Asignacion' => 'Año Asignacion',
            'Valor' => 'Valor'
        ];
        
        if (isset($sortColumnMap[$sortBy])) {
            $query->orderBy($sortColumnMap[$sortBy], $sortDir);
        } else {
            $query->orderBy('Año Asignacion', 'desc')->orderBy('Id Centro Balance', 'asc');
        }
        
        // Paginación - CORREGIDO: usando appends para preservar parámetros
        $items = $query->paginate(25)->appends($request->query());
        
        // Datos para filtros
        $centrosBalance = BalanceCenter::where('Activos', 1)->orderBy('Centro Balance', 'asc')->get();
        $lineasCredito = CreditLine::orderBy('Linea de Crédito', 'asc')->get();
        $fuentesFinanciamiento = FinancingSource::orderBy('Fuente Financiamiento', 'asc')->get();
        
        // Obtener años únicos para filtro
        $anios = Asignacion::select('Año Asignacion')
            ->distinct()
            ->orderBy('Año Asignacion', 'desc')
            ->pluck('Año Asignacion');
        
        return view('asignaciones.index', compact(
            'items', 
            'centrosBalance', 
            'lineasCredito', 
            'fuentesFinanciamiento',
            'anios'
        ));
    }

    public function create()
    {
        $centrosBalance = BalanceCenter::where('Activos', 1)->orderBy('Centro Balance', 'asc')->get();
        $lineasCredito = CreditLine::orderBy('Linea de Crédito', 'asc')->get();
        $fuentesFinanciamiento = FinancingSource::orderBy('Fuente Financiamiento', 'asc')->get();
        
        // Obtener años únicos para el formulario de creación
        $anios = Asignacion::select('Año Asignacion')
            ->distinct()
            ->orderBy('Año Asignacion', 'desc')
            ->pluck('Año Asignacion');
        
        return view('asignaciones.create', compact(
            'centrosBalance',
            'lineasCredito',
            'fuentesFinanciamiento',
            'anios'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Id_Centro_Balance' => 'required|exists:Nomenclador Centros Balance,Id Centro Balnce',
            'Id_Lineacredito' => 'required|exists:Nomenclador Líneas Crédito,Id Lineacredito',
            'Id_Fuentafinan' => 'required|exists:Nomenclador Fuentes Financiamiento,Id Fuentefinan',
            'Año_Asignacion' => 'required|integer|min:2000|max:2100',
            'Valor' => 'required|numeric|min:0'
        ]);
        
        // Mapear los nombres de los campos
        $data = [
            'Id Centro Balance' => $request->input('Id_Centro_Balance'),
            'Id Lineacredito' => $request->input('Id_Lineacredito'),
            'Id Fuentafinan' => $request->input('Id_Fuentafinan'),
            'Año Asignacion' => $request->input('Año_Asignacion'),
            'Valor' => $request->input('Valor')
        ];
        
        // Verificar si ya existe una asignación con la misma clave primaria
        $existe = Asignacion::where('Id Centro Balance', $data['Id Centro Balance'])
            ->where('Id Lineacredito', $data['Id Lineacredito'])
            ->where('Id Fuentafinan', $data['Id Fuentafinan'])
            ->where('Año Asignacion', $data['Año Asignacion'])
            ->exists();
        
        if ($existe) {
            return back()->withInput()->with('error', 'Ya existe una asignación con estos parámetros');
        }
        
        Asignacion::create($data);
        
        return redirect()->route('asignaciones.index')
            ->with('success', 'Asignación creada correctamente');
    }

    public function show($cb, $lc, $ff, $anio)
    {
        $asignacion = Asignacion::with(['centroBalance', 'lineaCredito', 'fuenteFinanciamiento'])
            ->where('Id Centro Balance', $cb)
            ->where('Id Lineacredito', $lc)
            ->where('Id Fuentafinan', $ff)
            ->where('Año Asignacion', $anio)
            ->firstOrFail();
        
        return view('asignaciones.show', compact('asignacion'));
    }

    public function edit($cb, $lc, $ff, $anio)
    {
        $asignacion = Asignacion::where('Id Centro Balance', $cb)
            ->where('Id Lineacredito', $lc)
            ->where('Id Fuentafinan', $ff)
            ->where('Año Asignacion', $anio)
            ->firstOrFail();
        
        $centrosBalance = BalanceCenter::where('Activos', 1)->orderBy('Centro Balance', 'asc')->get();
        $lineasCredito = CreditLine::orderBy('Linea de Crédito', 'asc')->get();
        $fuentesFinanciamiento = FinancingSource::orderBy('Fuente Financiamiento', 'asc')->get();
        
        // Obtener años únicos para el formulario de edición
        $anios = Asignacion::select('Año Asignacion')
            ->distinct()
            ->orderBy('Año Asignacion', 'desc')
            ->pluck('Año Asignacion');
        
        return view('asignaciones.edit', compact(
            'asignacion',
            'centrosBalance',
            'lineasCredito',
            'fuentesFinanciamiento',
            'anios'
        ));
    }

    public function update(Request $request, $cb, $lc, $ff, $anio)
    {
        $request->validate([
            'Id_Centro_Balance' => 'required|exists:Nomenclador Centros Balance,Id Centro Balnce',
            'Id_Lineacredito' => 'required|exists:Nomenclador Líneas Crédito,Id Lineacredito',
            'Id_Fuentafinan' => 'required|exists:Nomenclador Fuentes Financiamiento,Id Fuentefinan',
            'Año_Asignacion' => 'required|integer|min:2000|max:2100',
            'Valor' => 'required|numeric|min:0'
        ]);
        
        // Mapear los nombres de los campos
        $data = [
            'Id Centro Balance' => $request->input('Id_Centro_Balance'),
            'Id Lineacredito' => $request->input('Id_Lineacredito'),
            'Id Fuentafinan' => $request->input('Id_Fuentafinan'),
            'Año Asignacion' => $request->input('Año_Asignacion'),
            'Valor' => $request->input('Valor')
        ];
        
        // Buscar la asignación original
        $asignacion = Asignacion::where('Id Centro Balance', $cb)
            ->where('Id Lineacredito', $lc)
            ->where('Id Fuentafinan', $ff)
            ->where('Año Asignacion', $anio)
            ->firstOrFail();
        
        // Si cambió alguno de los campos que forman la clave primaria, verificar que no exista duplicado
        if ($data['Id Centro Balance'] != $cb ||
            $data['Id Lineacredito'] != $lc ||
            $data['Id Fuentafinan'] != $ff ||
            $data['Año Asignacion'] != $anio) {
            
            $existe = Asignacion::where('Id Centro Balance', $data['Id Centro Balance'])
                ->where('Id Lineacredito', $data['Id Lineacredito'])
                ->where('Id Fuentafinan', $data['Id Fuentafinan'])
                ->where('Año Asignacion', $data['Año Asignacion'])
                ->exists();
            
            if ($existe) {
                return back()->withInput()->with('error', 'Ya existe una asignación con estos parámetros');
            }
            
            // Eliminar el registro antiguo
            $asignacion->delete();
            
            // Crear uno nuevo
            Asignacion::create($data);
        } else {
            // Actualizar sin cambiar la clave primaria
            $asignacion->update($data);
        }
        
        return redirect()->route('asignaciones.index')
            ->with('success', 'Asignación actualizada correctamente');
    }

    public function destroy($cb, $lc, $ff, $anio)
    {
        $asignacion = Asignacion::where('Id Centro Balance', $cb)
            ->where('Id Lineacredito', $lc)
            ->where('Id Fuentafinan', $ff)
            ->where('Año Asignacion', $anio)
            ->firstOrFail();
        
        $asignacion->delete();
        
        return redirect()->route('asignaciones.index')
            ->with('success', 'Asignación eliminada correctamente');
    }
    
    // Métodos de exportación - GET
    
    public function exportExcel(Request $request)
    {
        $query = Asignacion::with(['centroBalance', 'lineaCredito', 'fuenteFinanciamiento']);
        
        // Aplicar filtros (misma lógica que el index)
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            // Convertir string separado por comas a array de IDs compuestos
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    foreach ($ids as $id) {
                        $parts = explode('-', $id);
                        if (count($parts) == 4) {
                            $q->orWhere(function($subQuery) use ($parts) {
                                $subQuery->where('Id Centro Balance', $parts[0])
                                    ->where('Id Lineacredito', $parts[1])
                                    ->where('Id Fuentafinan', $parts[2])
                                    ->where('Año Asignacion', $parts[3]);
                            });
                        }
                    }
                });
            }
        }
        
        $asignaciones = $query->get();
        
        return Excel::download(new AsignacionesExport($asignaciones), 'asignaciones.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Asignacion::with(['centroBalance', 'lineaCredito', 'fuenteFinanciamiento']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    foreach ($ids as $id) {
                        $parts = explode('-', $id);
                        if (count($parts) == 4) {
                            $q->orWhere(function($subQuery) use ($parts) {
                                $subQuery->where('Id Centro Balance', $parts[0])
                                    ->where('Id Lineacredito', $parts[1])
                                    ->where('Id Fuentafinan', $parts[2])
                                    ->where('Año Asignacion', $parts[3]);
                            });
                        }
                    }
                });
            }
        }
        
        $asignaciones = $query->get();
        
        $pdf = PDF::loadView('asignaciones.pdf', [
            'asignaciones' => $asignaciones,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('asignaciones.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Asignacion::with(['centroBalance', 'lineaCredito', 'fuenteFinanciamiento']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    foreach ($ids as $id) {
                        $parts = explode('-', $id);
                        if (count($parts) == 4) {
                            $q->orWhere(function($subQuery) use ($parts) {
                                $subQuery->where('Id Centro Balance', $parts[0])
                                    ->where('Id Lineacredito', $parts[1])
                                    ->where('Id Fuentafinan', $parts[2])
                                    ->where('Año Asignacion', $parts[3]);
                            });
                        }
                    }
                });
            }
        }
        
        $asignaciones = $query->get();
        
        return view('asignaciones.print', [
            'asignaciones' => $asignaciones,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected' => 'required|array',
        ]);
        
        $selected = $request->selected;
        $count = 0;
        
        foreach ($selected as $item) {
            $parts = explode('-', $item);
            if (count($parts) == 4) {
                $asignacion = Asignacion::where('Id Centro Balance', $parts[0])
                    ->where('Id Lineacredito', $parts[1])
                    ->where('Id Fuentafinan', $parts[2])
                    ->where('Año Asignacion', $parts[3])
                    ->first();
                
                if ($asignacion) {
                    $asignacion->delete();
                    $count++;
                }
            }
        }
        
        return redirect()->route('asignaciones.index')
            ->with('success', "Se eliminaron $count asignaciones correctamente.");
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('centroBalance', function($q) use ($search) {
                    $q->where('Centro Balance', 'like', "%{$search}%");
                })
                ->orWhereHas('lineaCredito', function($q) use ($search) {
                    $q->where('Linea de Crédito', 'like', "%{$search}%");
                })
                ->orWhereHas('fuenteFinanciamiento', function($q) use ($search) {
                    $q->where('Fuente Financiamiento', 'like', "%{$search}%");
                })
                ->orWhere('Año Asignacion', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('Id_Centro_Balance')) {
            $query->where('Id Centro Balance', $request->Id_Centro_Balance);
        }
        
        if ($request->filled('Id_Lineacredito')) {
            $query->where('Id Lineacredito', $request->Id_Lineacredito);
        }
        
        if ($request->filled('Id_Fuentafinan')) {
            $query->where('Id Fuentafinan', $request->Id_Fuentafinan);
        }
        
        if ($request->filled('Año_Asignacion')) {
            $query->where('Año Asignacion', $request->Año_Asignacion);
        }
        
        // Ordenación
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            
            $sortColumnMap = [
                'Centro Balance' => 'Id Centro Balance',
                'Linea Credito' => 'Id Lineacredito',
                'Fuente Financiamiento' => 'Id Fuentafinan',
                'Año Asignacion' => 'Año Asignacion',
                'Valor' => 'Valor'
            ];
            
            if (isset($sortColumnMap[$request->sort_by])) {
                $query->orderBy($sortColumnMap[$request->sort_by], $sortDir);
            }
        } else {
            $query->orderBy('Año Asignacion', 'desc')->orderBy('Id Centro Balance', 'asc');
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
        
        if ($request->filled('Id_Centro_Balance')) {
            $centro = BalanceCenter::find($request->Id_Centro_Balance);
            $filtros[] = "Centro Balance: " . ($centro ? $centro->{'Centro Balance'} : 'Desconocido');
        }
        
        if ($request->filled('Id_Lineacredito')) {
            $linea = CreditLine::find($request->Id_Lineacredito);
            $filtros[] = "Línea de Crédito: " . ($linea ? $linea->{'Linea de Crédito'} : 'Desconocido');
        }
        
        if ($request->filled('Id_Fuentafinan')) {
            $fuente = FinancingSource::find($request->Id_Fuentafinan);
            $filtros[] = "Fuente Financiamiento: " . ($fuente ? $fuente->{'Fuente Financiamiento'} : 'Desconocido');
        }
        
        if ($request->filled('Año_Asignacion')) {
            $filtros[] = "Año: " . $request->Año_Asignacion;
        }
        
        if ($request->filled('sort_by')) {
            $sortDir = $request->input('sort_dir', 'desc');
            $filtros[] = "Ordenado por: " . $request->sort_by . " (" . $sortDir . ")";
        }
        
        return $filtros;
    }
}