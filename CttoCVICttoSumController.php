<?php

namespace App\Http\Controllers;

use App\Models\CttoCVICttoSum;
use App\Models\DatosSOE;
use App\Models\ContratoSuministro;
use Illuminate\Http\Request;
use App\Exports\CttoCVICttoSumExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class CttoCVICttoSumController extends Controller
{
    public function index(Request $request)
    {
        $query = CttoCVICttoSum::with(['datosSOE', 'contratoSuministro']);
        
        // Aplicar filtros
        if ($request->filled('Id_SOE')) {
            $query->where('Id_SOE', $request->Id_SOE);
        }
        
        if ($request->filled('Id_Ctto_Sum')) {
            $query->where('Id_Ctto_Sum', $request->Id_Ctto_Sum);
        }
        
        $items = $query->paginate(20);
        
        return view('ctto-cvi-ctto-sum.index', compact('items'));
    }

    public function create()
    {
        $soes = DatosSOE::all();
        $contratos = ContratoSuministro::all();
        return view('ctto-cvi-ctto-sum.create', compact('soes', 'contratos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'Id_Ctto_Sum' => 'nullable|exists:Contratos Suministro,Id Cttosuministro',
        ]);

        CttoCVICttoSum::create($validated);
        
        return redirect()->route('ctto-cvi-ctto-sum.index')
            ->with('success', 'Relación CVI vs Ctto Sum creada exitosamente.');
    }

    public function show($id)
    {
        $item = CttoCVICttoSum::with(['datosSOE', 'contratoSuministro'])->findOrFail($id);
        return view('ctto-cvi-ctto-sum.show', compact('item'));
    }

    public function edit($id)
    {
        $item = CttoCVICttoSum::findOrFail($id);
        $soes = DatosSOE::all();
        $contratos = ContratoSuministro::all();
        return view('ctto-cvi-ctto-sum.edit', compact('item', 'soes', 'contratos'));
    }

    public function update(Request $request, $id)
    {
        $item = CttoCVICttoSum::findOrFail($id);
        
        $validated = $request->validate([
            'Id_SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'Id_Ctto_Sum' => 'nullable|exists:Contratos Suministro,Id Cttosuministro',
        ]);

        $item->update($validated);
        
        return redirect()->route('ctto-cvi-ctto-sum.index')
            ->with('success', 'Relación CVI vs Ctto Sum actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $item = CttoCVICttoSum::findOrFail($id);
        $item->delete();
        
        return redirect()->route('ctto-cvi-ctto-sum.index')
            ->with('success', 'Relación CVI vs Ctto Sum eliminada exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:CttoCVI vs CttoSum,Id'
        ]);
        
        $ids = $request->ids;
        $count = CttoCVICttoSum::whereIn('Id', $ids)->delete();
        
        return redirect()->route('ctto-cvi-ctto-sum.index')
            ->with('success', "Se eliminaron $count relaciones correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = CttoCVICttoSum::with(['datosSOE', 'contratoSuministro']);
        
        // Aplicar filtros
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
        
        return Excel::download(new CttoCVICttoSumExport($items), 'ctto-cvi-ctto-sum.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = CttoCVICttoSum::with(['datosSOE', 'contratoSuministro']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->get();
        
        $pdf = PDF::loadView('ctto-cvi-ctto-sum.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('ctto-cvi-ctto-sum.pdf');
    }

    public function print(Request $request)
    {
        $query = CttoCVICttoSum::with(['datosSOE', 'contratoSuministro']);
        
        $this->aplicarFiltros($query, $request);
        
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id', $ids);
            }
        }
        
        $items = $query->get();
        
        return view('ctto-cvi-ctto-sum.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('Id_SOE')) {
            $query->where('Id_SOE', $request->Id_SOE);
        }
        
        if ($request->filled('Id_Ctto_Sum')) {
            $query->where('Id_Ctto_Sum', $request->Id_Ctto_Sum);
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('Id_SOE')) {
            $filtros[] = "ID SOE: " . $request->Id_SOE;
        }
        
        if ($request->filled('Id_Ctto_Sum')) {
            $filtros[] = "ID Ctto Sum: " . $request->Id_Ctto_Sum;
        }
        
        return $filtros;
    }
}