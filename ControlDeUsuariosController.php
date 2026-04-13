<?php

namespace App\Http\Controllers;

use App\Models\ControlDeUsuarios;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ControlDeUsuariosExport;
use PDF;

class ControlDeUsuariosController extends Controller
{
    public function index(Request $request)
    {
        $query = ControlDeUsuarios::query();

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Id', 'like', "%{$search}%")
                  ->orWhere('PDW', 'like', "%{$search}%");
            });
        }

        // Ordenación
        $orderBy = $request->get('order_by', 'Id');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        $controlDeUsuarios = $query->paginate(25)->withQueryString();

        return view('control_de_usuarios.index', compact('controlDeUsuarios'));
    }

    public function create()
    {
        return view('control_de_usuarios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'PDW' => 'nullable|string|max:255',
            'Entrada' => 'nullable|date',
            'Salida' => 'nullable|date'
        ]);

        ControlDeUsuarios::create($request->all());

        return redirect()->route('control-de-usuarios.index')
            ->with('success', 'Registro creado correctamente');
    }

    public function show($id)
    {
        $controlDeUsuarios = ControlDeUsuarios::findOrFail($id);
        return view('control_de_usuarios.show', compact('controlDeUsuarios'));
    }

    public function edit($id)
    {
        $controlDeUsuarios = ControlDeUsuarios::findOrFail($id);
        return view('control_de_usuarios.edit', compact('controlDeUsuarios'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'PDW' => 'nullable|string|max:255',
            'Entrada' => 'nullable|date',
            'Salida' => 'nullable|date'
        ]);

        $controlDeUsuarios = ControlDeUsuarios::findOrFail($id);
        $controlDeUsuarios->update($request->all());

        return redirect()->route('control-de-usuarios.index')
            ->with('success', 'Registro actualizado correctamente');
    }

    public function destroy($id)
    {
        $controlDeUsuarios = ControlDeUsuarios::findOrFail($id);
        $controlDeUsuarios->delete();

        return redirect()->route('control-de-usuarios.index')
            ->with('success', 'Registro eliminado correctamente');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:Control de Usuarios,Id'
        ]);

        $count = ControlDeUsuarios::whereIn('Id', $request->selected_ids)->delete();

        return redirect()->route('control-de-usuarios.index')
            ->with('success', "{$count} registros eliminados correctamente");
    }

    public function exportExcel(Request $request)
    {
        $query = ControlDeUsuarios::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Id', 'like', "%{$search}%");
        }

        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('Id', $ids);
        }

        $controlDeUsuarios = $query->orderBy('Id')->get();

        return Excel::download(new ControlDeUsuariosExport($controlDeUsuarios), 'control-de-usuarios_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = ControlDeUsuarios::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Id', 'like', "%{$search}%");
        }

        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('Id', $ids);
        }

        $controlDeUsuarios = $query->orderBy('Id')->get();

        $pdf = PDF::loadView('control_de_usuarios.pdf', ['controlDeUsuarios' => $controlDeUsuarios]);
        return $pdf->download('control-de-usuarios_' . date('Y-m-d') . '.pdf');
    }

    public function print(Request $request)
    {
        $query = ControlDeUsuarios::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('Id', 'like', "%{$search}%");
        }

        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('Id', $ids);
        }

        $controlDeUsuarios = $query->orderBy('Id')->get();

        return view('control_de_usuarios.print', compact('controlDeUsuarios'));
    }
}
