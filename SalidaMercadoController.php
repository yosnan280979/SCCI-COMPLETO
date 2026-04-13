<?php

namespace App\Http\Controllers;

use App\Models\SalidasAlMercado;
use Illuminate\Http\Request;

class SalidaMercadoController extends Controller
{
    public function index()
    {
        $items = SalidasAlMercado::paginate(20);
        return view('salidas-mercado.index', compact('items'));
    }

    public function create()
    {
        return view('salidas-mercado.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'No_Sal_Mer' => 'required|integer',
            'Id_Especialista' => 'required|integer',
            'Año_Sal_Mer' => 'required|integer',
            'Fecha_sal_Mer' => 'nullable|date',
            'Fecha_pri_oferta' => 'nullable|date',
        ]);

        SalidasAlMercado::create($validated);
        
        return redirect()->route('salidas-mercado.index')
            ->with('success', 'Salida al mercado creada exitosamente.');
    }

    public function show($id)
    {
        $item = SalidasAlMercado::findOrFail($id);
        return view('salidas-mercado.show', compact('item'));
    }

    public function edit($id)
    {
        $item = SalidasAlMercado::findOrFail($id);
        return view('salidas-mercado.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = SalidasAlMercado::findOrFail($id);
        
        $validated = $request->validate([
            'No_Sal_Mer' => 'required|integer',
            'Id_Especialista' => 'required|integer',
            'Año_Sal_Mer' => 'required|integer',
            'Fecha_sal_Mer' => 'nullable|date',
            'Fecha_pri_oferta' => 'nullable|date',
        ]);

        $item->update($validated);
        
        return redirect()->route('salidas-mercado.index')
            ->with('success', 'Salida al mercado actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $item = SalidasAlMercado::findOrFail($id);
        $item->delete();
        
        return redirect()->route('salidas-mercado.index')
            ->with('success', 'Salida al mercado eliminada exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
        SalidasAlMercado::whereIn('Id_Salida_Mercado', $ids)->delete();
        
        return response()->json(['success' => true]);
    }

    public function exportExcel()
    {
        // Implementar exportación a Excel
        return redirect()->back()->with('info', 'Exportación a Excel en desarrollo.');
    }

    public function exportPdf()
    {
        // Implementar exportación a PDF
        return redirect()->back()->with('info', 'Exportación a PDF en desarrollo.');
    }

    public function print()
    {
        $items = SalidasAlMercado::all();
        return view('salidas-mercado.print', compact('items'));
    }
}