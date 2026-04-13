<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Exports\AreasExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::all();
        return view('nomencladores.areas.index', compact('areas'));
    }

    public function create()
    {
        return view('nomencladores.areas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Id Area' => 'required|unique:Nomenclador Areas,Id Area',
            'Area' => 'required|string|max:50'
        ]);

        Area::create($request->all());

        return redirect()->route('nomencladores.areas.index')
            ->with('success', 'Área creada correctamente.');
    }

    public function show($id)
    {
        $area = Area::findOrFail($id);
        return view('nomencladores.areas.show', compact('area'));
    }

    public function edit($id)
    {
        $area = Area::findOrFail($id);
        return view('nomencladores.areas.edit', compact('area'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Area' => 'required|string|max:50'
        ]);

        $area = Area::findOrFail($id);
        $area->update($request->all());

        return redirect()->route('nomencladores.areas.index')
            ->with('success', 'Área actualizada correctamente.');
    }

    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return redirect()->route('nomencladores.areas.index')
            ->with('success', 'Área eliminada correctamente.');
    }

    public function exportExcel(Request $request)
    {
        $areas = Area::query();
        
        if ($request->filled('selected')) {
            $ids = explode(',', $request->input('selected', ''));
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $areas->whereIn('Id Area', $ids);
            }
        }
        
        return Excel::download(new AreasExport($areas->get()), 'areas_' . date('Y-m-d') . '.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $areas = Area::query();
        
        if ($request->filled('selected')) {
            $ids = explode(',', $request->input('selected', ''));
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $areas->whereIn('Id Area', $ids);
            }
        }
        
        $pdf = PDF::loadView('nomencladores.areas.pdf', ['areas' => $areas->get()]);
        return $pdf->download('areas_' . date('Y-m-d') . '.pdf');
    }
    
    public function print(Request $request)
    {
        $areas = Area::query();
        
        if ($request->filled('selected')) {
            $ids = explode(',', $request->input('selected', ''));
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $areas->whereIn('Id Area', $ids);
            }
        }
        
        return view('nomencladores.areas.print', ['areas' => $areas->get()]);
    }
}