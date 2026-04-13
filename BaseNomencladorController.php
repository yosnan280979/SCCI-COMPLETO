<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class BaseNomencladorController extends Controller
{
    protected $model;
    protected $tableName;
    protected $primaryKey;
    protected $viewPath;
    protected $routePrefix;
    protected $searchFields = [];
    protected $exportColumns = [];
    protected $validationRules = [];
    
    public function index(Request $request)
    {
        $query = $this->model::query();
        
        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                foreach ($this->searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        
        // Filtros adicionales
        if ($request->has('status')) {
            $query->where('Activos', $request->status);
        }
        
        $items = $query->orderBy($this->getOrderColumn(), 'asc')->paginate(15);
        
        return view("{$this->viewPath}.index", compact('items'));
    }
    
    public function create()
    {
        return view("{$this->viewPath}.create");
    }
    
    public function store(Request $request)
    {
        $request->validate($this->validationRules);
        
        try {
            $this->model::create($request->all());
            return redirect()->route("{$this->routePrefix}.index")
                ->with('success', 'Registro creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el registro: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function show($id)
    {
        $item = $this->model::findOrFail($id);
        return view("{$this->viewPath}.show", compact('item'));
    }
    
    public function edit($id)
    {
        $item = $this->model::findOrFail($id);
        return view("{$this->viewPath}.edit", compact('item'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate($this->validationRules);
        
        try {
            $item = $this->model::findOrFail($id);
            $item->update($request->all());
            return redirect()->route("{$this->routePrefix}.index")
                ->with('success', 'Registro actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el registro: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function destroy($id)
    {
        try {
            $item = $this->model::findOrFail($id);
            $item->delete();
            return redirect()->route("{$this->routePrefix}.index")
                ->with('success', 'Registro eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar el registro: ' . $e->getMessage());
        }
    }
    
    public function exportExcel()
    {
        $data = $this->model::all();
        return Excel::download(new class($data, $this->exportColumns) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $data;
            protected $headings;
            
            public function __construct($data, $headings)
            {
                $this->data = $data;
                $this->headings = $headings;
            }
            
            public function collection()
            {
                return $this->data;
            }
            
            public function headings(): array
            {
                return $this->headings;
            }
        }, "{$this->tableName}.xlsx");
    }
    
    public function exportPdf()
    {
        $data = $this->model::all();
        $pdf = PDF::loadView("{$this->viewPath}.pdf", compact('data'));
        return $pdf->download("{$this->tableName}.pdf");
    }
    
    public function print()
    {
        $data = $this->model::all();
        return view("{$this->viewPath}.print", compact('data'));
    }
    
    protected function getOrderColumn()
    {
        // Intenta determinar la columna de ordenación
        $columns = $this->searchFields;
        return !empty($columns) ? $columns[0] : $this->primaryKey;
    }
}