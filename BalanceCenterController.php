<?php

namespace App\Http\Controllers;

use App\Models\BalanceCenter;
use App\Models\OSDE;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BalanceCenterExport;
use PDF;

class BalanceCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = BalanceCenter::with('osde');

        // Filtros
        if ($request->filled('search')) {
            $query->where('Centro Balance', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }
        
        if ($request->filled('osde_id')) {
            $query->where('Id OSDE', $request->osde_id);
        }

        // Ordenación
        $sortBy = $request->input('sort_by', 'Id Centro Balnce');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginación con preservación de parámetros
        $items = $query->paginate(20)->appends($request->query());
        $osdes = OSDE::all();

        return view('nomencladores.balance_centers.index', compact('items', 'osdes'));
    }

    public function create()
    {
        $osdes = OSDE::all();
        return view('nomencladores.balance_centers.create', compact('osdes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Centro Balance' => 'required|string|max:50|unique:Nomenclador Centros Balance,Centro Balance',
            'Activos' => 'required|boolean',
            'Id OSDE' => 'nullable|exists:Nomenclador OSDE,Id Osde'
        ]);

        BalanceCenter::create($request->all());

        return redirect()->route('nomencladores.balance-centers.index')
            ->with('success', 'Centro de Balance creado correctamente');
    }

    public function show($id)
    {
        $balanceCenter = BalanceCenter::with('osde')->findOrFail($id);
        return view('nomencladores.balance_centers.show', compact('balanceCenter'));
    }

    public function edit($id)
    {
        $balanceCenter = BalanceCenter::findOrFail($id);
        $osdes = OSDE::all();
        return view('nomencladores.balance_centers.edit', compact('balanceCenter', 'osdes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Centro Balance' => 'required|string|max:50|unique:Nomenclador Centros Balance,Centro Balance,' . $id . ',Id Centro Balnce',
            'Activos' => 'required|boolean',
            'Id OSDE' => 'nullable|exists:Nomenclador OSDE,Id Osde'
        ]);

        $balanceCenter = BalanceCenter::findOrFail($id);
        $balanceCenter->update($request->all());

        return redirect()->route('nomencladores.balance-centers.index')
            ->with('success', 'Centro de Balance actualizado correctamente');
    }

    public function destroy($id)
    {
        $balanceCenter = BalanceCenter::findOrFail($id);
        $balanceCenter->delete();

        return redirect()->route('nomencladores.balance-centers.index')
            ->with('success', 'Centro de Balance eliminado correctamente');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron centros de balance para eliminar.');
        }
        
        $count = BalanceCenter::whereIn('Id Centro Balnce', $ids)->delete();

        return redirect()->route('nomencladores.balance-centers.index')
            ->with('success', "Se eliminaron $count centros de balance correctamente.");
    }

    public function exportExcel(Request $request)
    {
        $query = BalanceCenter::with('osde');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('Centro Balance', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }
        
        if ($request->filled('osde_id')) {
            $query->where('Id OSDE', $request->osde_id);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Centro Balnce', $ids);
            }
        }
        
        $items = $query->orderBy('Centro Balance')->get();
        
        return Excel::download(new BalanceCenterExport($items), 'centros_balance.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = BalanceCenter::with('osde');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('Centro Balance', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }
        
        if ($request->filled('osde_id')) {
            $query->where('Id OSDE', $request->osde_id);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Centro Balnce', $ids);
            }
        }
        
        $items = $query->orderBy('Centro Balance')->get();
        
        $pdf = PDF::loadView('nomencladores.balance_centers.pdf', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('centros_balance.pdf');
    }

    public function print(Request $request)
    {
        $query = BalanceCenter::with('osde');
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('Centro Balance', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('activo')) {
            $query->where('Activos', $request->activo);
        }
        
        if ($request->filled('osde_id')) {
            $query->where('Id OSDE', $request->osde_id);
        }
        
        // Manejar selección de registros
        $selected = $request->input('selected_ids', '');
        
        if (!empty($selected)) {
            $ids = explode(',', $selected);
            $ids = array_filter($ids);
            if (!empty($ids)) {
                $query->whereIn('Id Centro Balnce', $ids);
            }
        }
        
        $items = $query->orderBy('Centro Balance')->get();
        
        return view('nomencladores.balance_centers.print', [
            'items' => $items,
            'filtros' => $this->obtenerFiltros($request)
        ]);
    }
    
    private function obtenerFiltros(Request $request)
    {
        $filtros = [];
        
        if ($request->filled('search')) {
            $filtros[] = "Búsqueda: " . $request->search;
        }
        
        if ($request->filled('activo')) {
            $filtros[] = "Activo: " . ($request->activo == '1' ? 'Sí' : 'No');
        }
        
        if ($request->filled('osde_id')) {
            $osde = OSDE::find($request->osde_id);
            $filtros[] = "OSDE: " . ($osde ? $osde->OSDE : 'Desconocido');
        }
        
        return $filtros;
    }
}