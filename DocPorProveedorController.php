<?php

namespace App\Http\Controllers;

use App\Models\DocPorProveedor;
use App\Models\Provider;
use App\Models\Document;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocPorProveedorExport;
use PDF;

class DocPorProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = DocPorProveedor::with(['proveedor','documento']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('proveedor', function($subq) use ($search) {
                    $subq->where('Proveedor','like',"%{$search}%");
                })->orWhereHas('documento', function($subq) use ($search) {
                    $subq->where('Documento','like',"%{$search}%");
                });
            });
        }
        if ($request->filled('proveedor')) {
            $query->where('Id Proveedor',$request->proveedor);
        }
        if ($request->filled('documento')) {
            $query->where('Id Documento',$request->documento);
        }
        if ($request->filled('caduca')) {
            $query->where('Caduca',$request->caduca);
        }

        $items = $query->paginate(20)->withQueryString();
        $proveedores = Provider::orderBy('Proveedor')->get();
        $documentos  = Document::orderBy('Documento')->get();

        return view('documentos-proveedor.index', compact('items','proveedores','documentos'));
    }

    public function create()
    {
        $proveedores = Provider::orderBy('Proveedor')->get();
        $documentos  = Document::orderBy('Documento')->get();
        return view('documentos-proveedor.create', compact('proveedores','documentos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Id_Proveedor'    => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Id_Documento'    => 'required|exists:Nomenclador Documentos,Id Documento',
            'Caduca'          => 'required|boolean',
            'Fecha_Caducidad' => 'nullable|date',
        ]);

        // Convertir nombres para coincidir con la base de datos
        $data = [
            'Id Proveedor' => $validated['Id_Proveedor'],
            'Id Documento' => $validated['Id_Documento'],
            'Caduca' => $validated['Caduca'],
            'Fecha Caducidad' => $validated['Fecha_Caducidad'] ?? null,
        ];

        DocPorProveedor::create($data);

        return redirect()->route('documentos-proveedor.index')
            ->with('success','Documento por proveedor creado exitosamente.');
    }

    public function show($idProveedor, $idDocumento)
    {
        $item = DocPorProveedor::where('Id Proveedor', $idProveedor)
                               ->where('Id Documento', $idDocumento)
                               ->with(['proveedor','documento'])
                               ->firstOrFail();
        return view('documentos-proveedor.show', compact('item'));
    }

    public function edit($idProveedor, $idDocumento)
    {
        $item = DocPorProveedor::where('Id Proveedor', $idProveedor)
                               ->where('Id Documento', $idDocumento)
                               ->firstOrFail();
        $proveedores = Provider::orderBy('Proveedor')->get();
        $documentos  = Document::orderBy('Documento')->get();

        return view('documentos-proveedor.edit', compact('item','proveedores','documentos'));
    }

    public function update(Request $request, $idProveedor, $idDocumento)
    {
        $item = DocPorProveedor::where('Id Proveedor', $idProveedor)
                               ->where('Id Documento', $idDocumento)
                               ->firstOrFail();

        $validated = $request->validate([
            'Id_Proveedor'    => 'required|exists:Nomenclador Proveedores,Id Proveedor',
            'Id_Documento'    => 'required|exists:Nomenclador Documentos,Id Documento',
            'Caduca'          => 'required|boolean',
            'Fecha_Caducidad' => 'nullable|date',
        ]);

        // Convertir nombres para coincidir con la base de datos
        $data = [
            'Id Proveedor' => $validated['Id_Proveedor'],
            'Id Documento' => $validated['Id_Documento'],
            'Caduca' => $validated['Caduca'],
            'Fecha Caducidad' => $validated['Fecha_Caducidad'] ?? null,
        ];

        $item->update($data);

        return redirect()->route('documentos-proveedor.index')
            ->with('success','Documento por proveedor actualizado exitosamente.');
    }

    public function destroy($idProveedor, $idDocumento)
    {
        $item = DocPorProveedor::where('Id Proveedor', $idProveedor)
                               ->where('Id Documento', $idDocumento)
                               ->firstOrFail();
        $item->delete();

        return redirect()->route('documentos-proveedor.index')
            ->with('success','Documento por proveedor eliminado exitosamente.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        $count = 0;
        
        foreach ($ids as $id) {
            $parts = explode('-', $id);
            if (count($parts) === 2) {
                $deleted = DocPorProveedor::where('Id Proveedor', $parts[0])
                                         ->where('Id Documento', $parts[1])
                                         ->delete();
                if ($deleted) $count++;
            }
        }
        
        return redirect()->route('documentos-proveedor.index')
            ->with('success', "Se eliminaron $count documentos correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = DocPorProveedor::with(['proveedor','documento']);

        // Aplicar los mismos filtros que en index
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    foreach ($ids as $id) {
                        $parts = explode('-', $id);
                        if (count($parts) === 2) {
                            $q->orWhere(function($subq) use ($parts) {
                                $subq->where('Id Proveedor', $parts[0])
                                     ->where('Id Documento', $parts[1]);
                            });
                        }
                    }
                });
            }
        }

        $items = $query->get();

        return Excel::download(new DocPorProveedorExport($items), 'documentos_proveedor_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = DocPorProveedor::with(['proveedor','documento']);

        // Aplicar los mismos filtros que en index
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    foreach ($ids as $id) {
                        $parts = explode('-', $id);
                        if (count($parts) === 2) {
                            $q->orWhere(function($subq) use ($parts) {
                                $subq->where('Id Proveedor', $parts[0])
                                     ->where('Id Documento', $parts[1]);
                            });
                        }
                    }
                });
            }
        }

        $items = $query->get();

        $pdf = PDF::loadView('documentos-proveedor.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
        
        return $pdf->download('documentos_proveedor_' . date('Y-m-d') . '.pdf');
    }

    public function print(Request $request)
    {
        $query = DocPorProveedor::with(['proveedor','documento']);

        // Aplicar los mismos filtros que en index
        $this->aplicarFiltros($query, $request);
        
        // Manejar selección de registros
        $selected = $request->input('selected', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    foreach ($ids as $id) {
                        $parts = explode('-', $id);
                        if (count($parts) === 2) {
                            $q->orWhere(function($subq) use ($parts) {
                                $subq->where('Id Proveedor', $parts[0])
                                     ->where('Id Documento', $parts[1]);
                            });
                        }
                    }
                });
            }
        }

        $items = $query->get();

        return view('documentos-proveedor.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    /**
     * Aplicar filtros comunes a las consultas
     */
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('proveedor', function($subq) use ($search) {
                    $subq->where('Proveedor','like',"%{$search}%");
                })->orWhereHas('documento', function($subq) use ($search) {
                    $subq->where('Documento','like',"%{$search}%");
                });
            });
        }
        
        if ($request->filled('proveedor')) {
            $query->where('Id Proveedor', $request->proveedor);
        }
        
        if ($request->filled('documento')) {
            $query->where('Id Documento', $request->documento);
        }
        
        if ($request->filled('caduca')) {
            $query->where('Caduca', $request->caduca);
        }
    }
    
    /**
     * Obtener texto de filtros aplicados
     */
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('proveedor')) {
            $proveedor = Provider::find($request->proveedor);
            $filtros[] = "Proveedor: " . ($proveedor ? $proveedor->Proveedor : 'Desconocido');
        }
        
        if ($request->filled('documento')) {
            $documento = Document::find($request->documento);
            $filtros[] = "Documento: " . ($documento ? $documento->Documento : 'Desconocido');
        }
        
        if ($request->filled('caduca')) {
            $filtros[] = "Caduca: " . ($request->caduca == '1' ? 'Sí' : 'No');
        }
        
        return $filtros;
    }
}