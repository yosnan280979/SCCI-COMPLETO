<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentsExport;
use PDF;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query();
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Documento', 'like', "%{$search}%");
        }
        
        if ($request->filled('imprescindible')) {
            $query->where('Imprescindible', $request->imprescindible);
        }
        
        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Documento');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación con preservación de parámetros
        $documents = $query->paginate(20)->appends($request->query());
        
        return view('nomencladores.documents.index', compact('documents'));
    }

    public function create()
    {
        return view('nomencladores.documents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Documento' => 'required|string|max:255',
            'Imprescindible' => 'nullable|boolean',
        ]);
        
        Document::create($validated);
        
        return redirect()->route('nomencladores.documents.index')
            ->with('success', 'Documento creado exitosamente.');
    }

    public function show($id)
    {
        $document = Document::findOrFail($id);
        return view('nomencladores.documents.show', compact('document'));
    }

    public function edit($id)
    {
        $document = Document::findOrFail($id);
        return view('nomencladores.documents.edit', compact('document'));
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        
        $validated = $request->validate([
            'Documento' => 'required|string|max:255',
            'Imprescindible' => 'nullable|boolean',
        ]);
        
        $document->update($validated);
        
        return redirect()->route('nomencladores.documents.index')
            ->with('success', 'Documento actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        $document->delete();
        
        return redirect()->route('nomencladores.documents.index')
            ->with('success', 'Documento eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron documentos para eliminar.');
        }
        
        $count = Document::whereIn('Id Documento', $ids)->delete();
        
        return redirect()->route('nomencladores.documents.index')
            ->with('success', "Se eliminaron $count documentos correctamente.");
    }

    // Exportaciones
    public function exportExcel(Request $request)
    {
        $query = Document::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Documento', 'like', "%{$search}%");
        }
        
        if ($request->filled('imprescindible')) {
            $query->where('Imprescindible', $request->imprescindible);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Documento', $ids);
            }
        }
        
        $documents = $query->get();
        
        return Excel::download(new DocumentsExport($documents), 'documentos.xlsx');
    }
    
    public function exportPdf(Request $request)
    {
        $query = Document::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Documento', 'like', "%{$search}%");
        }
        
        if ($request->filled('imprescindible')) {
            $query->where('Imprescindible', $request->imprescindible);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Documento', $ids);
            }
        }
        
        $documents = $query->get();
        
        $pdf = PDF::loadView('nomencladores.documents.pdf', [
            'documents' => $documents,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('documentos.pdf');
    }
    
    public function print(Request $request)
    {
        $query = Document::query();
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Documento', 'like', "%{$search}%");
        }
        
        if ($request->filled('imprescindible')) {
            $query->where('Imprescindible', $request->imprescindible);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Documento', $ids);
            }
        }
        
        $documents = $query->get();
        
        return view('nomencladores.documents.print', [
            'documents' => $documents,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('imprescindible')) {
            $filtros[] = "Imprescindible: " . ($request->imprescindible == '1' ? 'Sí' : 'No');
        }
        
        return $filtros;
    }
}