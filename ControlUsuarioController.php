<?php

namespace App\Http\Controllers;

use App\Models\ControlUsuario;
use Illuminate\Http\Request;
use DataTables;
use App\Exports\ControlUsuarioExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ControlUsuarioController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getData($request);
        }
        return view('controlusuario.index');
    }

    private function getData(Request $request)
    {
        $query = ControlUsuario::query();

        if ($request->has('entrada_from') && $request->entrada_from) {
            $query->whereDate('entrada', '>=', $request->entrada_from);
        }
        if ($request->has('entrada_to') && $request->entrada_to) {
            $query->whereDate('entrada', '<=', $request->entrada_to);
        }
        if ($request->has('salida_from') && $request->salida_from) {
            $query->whereDate('salida', '>=', $request->salida_from);
        }
        if ($request->has('salida_to') && $request->salida_to) {
            $query->whereDate('salida', '<=', $request->salida_to);
        }
        return DataTables::eloquent($query)
            ->addColumn('actions', function($control_usuario) {
                return view('controlusuario.partials.actions', compact('control_usuario'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('controlusuario.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'nullable',
            'entrada' => 'nullable',
            'salida' => 'nullable',
        ]);

        ControlUsuario::create($request->all());
        return redirect()->route('controlusuario.index')->with('success', 'ControlUsuario creado exitosamente.');
    }

    public function show(ControlUsuario $control_usuario)
    {
        return view('controlusuario.show', compact('control_usuario'));
    }

    public function edit(ControlUsuario $control_usuario)
    {
        return view('controlusuario.edit', compact('control_usuario'));
    }

    public function update(Request $request, ControlUsuario $control_usuario)
    {
        $request->validate([
            'usuario_id' => 'nullable',
            'entrada' => 'nullable',
            'salida' => 'nullable',
        ]);

        $control_usuario->update($request->all());
        return redirect()->route('controlusuario.index')->with('success', 'ControlUsuario actualizado exitosamente.');
    }

    public function destroy(ControlUsuario $control_usuario)
    {
        $control_usuario->delete();
        return response()->json(['success' => 'ControlUsuario eliminado exitosamente.']);
    }

    public function export()
    {
        $fields = [
            'id' => 'ID',
            'usuario_id' => 'Usuario ID',
            'entrada' => 'Entrada',
            'salida' => 'Salida',
        ];
        return view('controlusuario.export', compact('fields'));
    }

    public function exportProcess(Request $request)
    {
        $request->validate([
            'format' => 'required|in:excel,csv,pdf',
            'columns' => 'array',
            'columns.*' => 'string'
        ]);

        $query = ControlUsuario::query();

        if ($request->has('entrada_from') && $request->entrada_from) {
            $query->whereDate('entrada', '>=', $request->entrada_from);
        }
        if ($request->has('entrada_to') && $request->entrada_to) {
            $query->whereDate('entrada', '<=', $request->entrada_to);
        }
        if ($request->has('salida_from') && $request->salida_from) {
            $query->whereDate('salida', '>=', $request->salida_from);
        }
        if ($request->has('salida_to') && $request->salida_to) {
            $query->whereDate('salida', '<=', $request->salida_to);
        }
        $data = $query->get();

        $columns = $request->columns ?? array_keys($fields);
        $filename = 'controlusuario_' . date('Ymd_His') . '.' . $request->format;

        switch ($request->format) {
            case 'excel':
                return Excel::download(new ControlUsuarioExport($data, $columns), $filename);
            case 'csv':
                return Excel::download(new ControlUsuarioExport($data, $columns), $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'pdf':
                $pdf = PDF::loadView('controlusuario.pdf', compact('data'));
                return $pdf->download($filename);
        }
    }

    public function pdf()
    {
        $data = ControlUsuario::all();
        return view('controlusuario.pdf', compact('data'));
    }
}
