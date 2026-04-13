<?php

namespace App\Http\Controllers;

use App\Models\Reclamacion;
use App\Models\Embarque;
use Illuminate\Http\Request;

class ReclamacionController extends Controller
{
    public function index()
    {
        $items = Reclamacion::with('embarque')->paginate(20);
        return view('reclamaciones.index', compact('items'));
    }

    public function create()
    {
        $embarques = Embarque::all();
        return view('reclamaciones.create', compact('embarques'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_embarque' => 'nullable|exists:Embarques,Id Embarque',
            'Descripcion' => 'nullable|string|max:50',
        ]);

        Reclamacion::create($validated);
        
        return redirect()->route('reclamaciones.index')
            ->with('success', 'Reclamación creada exitosamente.');
    }

    public function show($id)
    {
        $item = Reclamacion::with('embarque')->findOrFail($id);
        return view('reclamaciones.show', compact('item'));
    }

    public function edit($id)
    {
        $item = Reclamacion::findOrFail($id);
        $embarques = Embarque::all();
        return view('reclamaciones.edit', compact('item', 'embarques'));
    }

    public function update(Request $request, $id)
    {
        $item = Reclamacion::findOrFail($id);
        
        $validated = $request->validate([
            'Id_embarque' => 'nullable|exists:Embarques,Id Embarque',
            'Descripcion' => 'nullable|string|max:50',
        ]);

        $item->update($validated);
        
        return redirect()->route('reclamaciones.index')
            ->with('success', 'Reclamación actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $item = Reclamacion::findOrFail($id);
        $item->delete();
        
        return redirect()->route('reclamaciones.index')
            ->with('success', 'Reclamación eliminada exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
        Reclamacion::whereIn('Id_Reclamacion', $ids)->delete();
        
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
        $items = Reclamacion::with('embarque')->get();
        return view('reclamaciones.print', compact('items'));
    }
}