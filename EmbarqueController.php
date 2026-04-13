<?php

namespace App\Http\Controllers;

use App\Models\Embarque;
use App\Models\DatosSOE;
use Illuminate\Http\Request;
use App\Exports\EmbarquesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class EmbarqueController extends Controller
{
    public function index()
    {
        $items = Embarque::with('datosSOE')->paginate(20);
        return view('embarques.index', compact('items'));
    }

    public function create()
    {
        $soes = DatosSOE::all();
        return view('embarques.create', compact('soes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'ETA' => 'nullable|date',
            'Fecha_Ent_s_Ctto' => 'nullable|date',
            'Fecha_real_arribo' => 'nullable|date',
            'Fecha_ent_cliente' => 'nullable|date',
            'Tipo_Embarque' => 'nullable|string|max:50',
        ]);

        Embarque::create($validated);
        
        return redirect()->route('embarques.index')
            ->with('success', 'Embarque creado exitosamente.');
    }

    public function show($id)
    {
        $item = Embarque::with('datosSOE')->findOrFail($id);
        return view('embarques.show', compact('item'));
    }

    public function edit($id)
    {
        $item = Embarque::findOrFail($id);
        $soes = DatosSOE::all();
        return view('embarques.edit', compact('item', 'soes'));
    }

    public function update(Request $request, $id)
    {
        $item = Embarque::findOrFail($id);
        
        $validated = $request->validate([
            'Id_SOE' => 'nullable|exists:Datos SOE,Id SOE',
            'ETA' => 'nullable|date',
            'Fecha_Ent_s_Ctto' => 'nullable|date',
            'Fecha_real_arribo' => 'nullable|date',
            'Fecha_ent_cliente' => 'nullable|date',
            'Tipo_Embarque' => 'nullable|string|max:50',
        ]);

        $item->update($validated);
        
        return redirect()->route('embarques.index')
            ->with('success', 'Embarque actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $item = Embarque::findOrFail($id);
        $item->delete();
        
        return redirect()->route('embarques.index')
            ->with('success', 'Embarque eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
        Embarque::whereIn('Id_Embarque', $ids)->delete();
        
        return response()->json(['success' => true]);
    }

    /** 📊 Exportar a Excel */
    public function exportExcel()
    {
        return Excel::download(new EmbarquesExport, 'embarques.xlsx');
    }

    /** 📄 Exportar a PDF */
    public function exportPdf()
    {
        $items = Embarque::with('datosSOE')->get();
        $pdf = Pdf::loadView('embarques.pdf', compact('items'));
        return $pdf->download('embarques.pdf');
    }

    /** 🖨️ Vista para imprimir */
    public function print()
    {
        $items = Embarque::with('datosSOE')->get();
        return view('embarques.print', compact('items'));
    }
}
