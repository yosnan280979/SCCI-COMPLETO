<?php

namespace App\Http\Controllers;

use App\Models\PagoCliente;
use App\Models\ContratoSuministro;
use Illuminate\Http\Request;

class PagoClienteController extends Controller
{
    public function index()
    {
        $items = PagoCliente::with('contratoSuministro')->paginate(20);
        return view('pagos-cliente.index', compact('items'));
    }

    public function create()
    {
        $contratos = ContratoSuministro::all();
        return view('pagos-cliente.create', compact('contratos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_Cttosuministro' => 'nullable|exists:Contratos Suministro,Id Cttosuministro',
            'No_Elemento_Pago' => 'nullable|string|max:50',
            'Valor_MN' => 'nullable|numeric',
            'Valor_CUC' => 'nullable|numeric',
            'Observaciones' => 'nullable|string',
        ]);

        PagoCliente::create($validated);
        
        return redirect()->route('pagos-cliente.index')
            ->with('success', 'Pago por cliente creado exitosamente.');
    }

    public function show($id)
    {
        $item = PagoCliente::with('contratoSuministro')->findOrFail($id);
        return view('pagos-cliente.show', compact('item'));
    }

    public function edit($id)
    {
        $item = PagoCliente::findOrFail($id);
        $contratos = ContratoSuministro::all();
        return view('pagos-cliente.edit', compact('item', 'contratos'));
    }

    public function update(Request $request, $id)
    {
        $item = PagoCliente::findOrFail($id);
        
        $validated = $request->validate([
            'Id_Cttosuministro' => 'nullable|exists:Contratos Suministro,Id Cttosuministro',
            'No_Elemento_Pago' => 'nullable|string|max:50',
            'Valor_MN' => 'nullable|numeric',
            'Valor_CUC' => 'nullable|numeric',
            'Observaciones' => 'nullable|string',
        ]);

        $item->update($validated);
        
        return redirect()->route('pagos-cliente.index')
            ->with('success', 'Pago por cliente actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $item = PagoCliente::findOrFail($id);
        $item->delete();
        
        return redirect()->route('pagos-cliente.index')
            ->with('success', 'Pago por cliente eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
        PagoCliente::whereIn('Id', $ids)->delete();
        
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
        $items = PagoCliente::with('contratoSuministro')->get();
        return view('pagos-cliente.print', compact('items'));
    }
}