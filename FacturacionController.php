<?php

namespace App\Http\Controllers;

use App\Models\Facturacion;
use App\Models\Embarque;
use Illuminate\Http\Request;

class FacturacionController extends Controller
{
    public function index()
    {
        $items = Facturacion::with('embarque')->paginate(20);
        return view('facturacion.index', compact('items'));
    }

    public function create()
    {
        $embarques = Embarque::all();
        return view('facturacion.create', compact('embarques'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_embarque' => 'nullable|exists:Embarques,Id Embarque',
            'No_Factura' => 'nullable|string|max:50',
            'Fecha_Factura' => 'nullable|string|max:50',
            'Valor_CUC' => 'nullable|numeric',
            'Valor_CUP' => 'nullable|numeric',
        ]);

        Facturacion::create($validated);
        
        return redirect()->route('facturacion.index')
            ->with('success', 'Facturación creada exitosamente.');
    }

    public function show($id)
    {
        $item = Facturacion::with('embarque')->findOrFail($id);
        return view('facturacion.show', compact('item'));
    }

    public function edit($id)
    {
        $item = Facturacion::findOrFail($id);
        $embarques = Embarque::all();
        return view('facturacion.edit', compact('item', 'embarques'));
    }

    public function update(Request $request, $id)
    {
        $item = Facturacion::findOrFail($id);
        
        $validated = $request->validate([
            'Id_embarque' => 'nullable|exists:Embarques,Id Embarque',
            'No_Factura' => 'nullable|string|max:50',
            'Fecha_Factura' => 'nullable|string|max:50',
            'Valor_CUC' => 'nullable|numeric',
            'Valor_CUP' => 'nullable|numeric',
        ]);

        $item->update($validated);
        
        return redirect()->route('facturacion.index')
            ->with('success', 'Facturación actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $item = Facturacion::findOrFail($id);
        $item->delete();
        
        return redirect()->route('facturacion.index')
            ->with('success', 'Facturación eliminada exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
        Facturacion::whereIn('Id_Facturacion', $ids)->delete();
        
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
        $items = Facturacion::with('embarque')->get();
        return view('facturacion.print', compact('items'));
    }
}