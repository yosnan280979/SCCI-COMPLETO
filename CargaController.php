<?php

namespace App\Http\Controllers;

use App\Models\Carga;
use App\Models\LoadType;
use App\Models\Embarque;
use Illuminate\Http\Request;

class CargaController extends Controller
{
    public function index(Request $request)
    {
        $query = Carga::query();
        
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Id Tipo Carga', 'like', "%{$search}%")
                  ->orWhere('Id Embarque', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por tipo de carga
        if ($request->filled('id_tipo_carga')) {
            $query->where('Id Tipo Carga', $request->id_tipo_carga);
        }
        
        // Ordenar
        $orderBy = $request->input('order_by', 'Id Tipo Carga');
        $query->orderBy($orderBy);
        
        $cargas = $query->paginate(20);
        
        // Datos para filtros
        $tiposCarga = LoadType::all();
        $embarques = Embarque::all();
        
        return view('carga.index', compact('cargas', 'tiposCarga', 'embarques'));
    }
    
    public function create()
    {
        $tiposCarga = LoadType::all();
        $embarques = Embarque::all();
        return view('carga.create', compact('tiposCarga', 'embarques'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id Tipo Carga' => 'required|integer|exists:Nomenclador Tipo Carga,Id Tipo Carga',
            'Id Embarque' => 'required|integer|exists:Embarques,Id Embarque',
            'Cantidad' => 'nullable|numeric|min:0',
            'Real' => 'nullable|numeric|min:0',
        ]);
        
        Carga::create($validated);
        return redirect()->route('cargas.index')->with('success', 'Carga creada correctamente.');
    }
    
    public function show(Carga $carga)
    {
        $carga->load(['tipoCarga', 'embarque']);
        return view('carga.show', compact('carga'));
    }
    
    public function edit(Carga $carga)
    {
        $tiposCarga = LoadType::all();
        $embarques = Embarque::all();
        return view('carga.edit', compact('carga', 'tiposCarga', 'embarques'));
    }
    
    public function update(Request $request, Carga $carga)
    {
        $validated = $request->validate([
            'Id Tipo Carga' => 'required|integer|exists:Nomenclador Tipo Carga,Id Tipo Carga',
            'Id Embarque' => 'required|integer|exists:Embarques,Id Embarque',
            'Cantidad' => 'nullable|numeric|min:0',
            'Real' => 'nullable|numeric|min:0',
        ]);
        
        $carga->update($validated);
        return redirect()->route('cargas.index')->with('success', 'Carga actualizada correctamente.');
    }
    
    public function destroy(Carga $carga)
    {
        $carga->delete();
        return redirect()->route('cargas.index')->with('success', 'Carga eliminada correctamente.');
    }
    
    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids');
        if ($ids) {
            Carga::whereIn('Id Tipo Carga', $ids)->delete();
            return response()->json(['success' => true, 'message' => 'Cargas eliminadas correctamente.']);
        }
        return response()->json(['success' => false, 'message' => 'No se seleccionaron cargas.'], 400);
    }
    
    public function exportExcel()
    {
        // Implementar exportación Excel
        return response()->download(storage_path('app/exports/cargas.xlsx'));
    }
    
    public function exportPdf()
    {
        // Implementar exportación PDF
        return response()->download(storage_path('app/exports/cargas.pdf'));
    }
    
    public function print()
    {
        $cargas = Carga::with(['tipoCarga', 'embarque'])->get();
        return view('carga.print', compact('cargas'));
    }
}