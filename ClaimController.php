<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use Illuminate\Http\Request;
use DataTables;
use App\Exports\ClaimExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getData($request);
        }
        return view('claim.index');
    }

    private function getData(Request $request)
    {
        $query = Claim::query();

        return DataTables::eloquent($query)
            ->addColumn('actions', function($claim) {
                return view('claim.partials.actions', compact('claim'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('claim.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'embarque_id' => 'nullable',
            'descripcion' => 'nullable',
        ]);

        Claim::create($request->all());
        return redirect()->route('claim.index')->with('success', 'Claim creado exitosamente.');
    }

    public function show(Claim $claim)
    {
        return view('claim.show', compact('claim'));
    }

    public function edit(Claim $claim)
    {
        return view('claim.edit', compact('claim'));
    }

    public function update(Request $request, Claim $claim)
    {
        $request->validate([
            'embarque_id' => 'nullable',
            'descripcion' => 'nullable',
        ]);

        $claim->update($request->all());
        return redirect()->route('claim.index')->with('success', 'Claim actualizado exitosamente.');
    }

    public function destroy(Claim $claim)
    {
        $claim->delete();
        return response()->json(['success' => 'Claim eliminado exitosamente.']);
    }

    public function export()
    {
        $fields = [
            'id' => 'ID',
            'embarque_id' => 'Embarque ID',
            'descripcion' => 'Descripción',
        ];
        return view('claim.export', compact('fields'));
    }

    public function exportProcess(Request $request)
    {
        $request->validate([
            'format' => 'required|in:excel,csv,pdf',
            'columns' => 'array',
            'columns.*' => 'string'
        ]);

        $query = Claim::query();

        $data = $query->get();

        $columns = $request->columns ?? array_keys($fields);
        $filename = 'claim_' . date('Ymd_His') . '.' . $request->format;

        switch ($request->format) {
            case 'excel':
                return Excel::download(new ClaimExport($data, $columns), $filename);
            case 'csv':
                return Excel::download(new ClaimExport($data, $columns), $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'pdf':
                $pdf = PDF::loadView('claim.pdf', compact('data'));
                return $pdf->download($filename);
        }
    }

    public function pdf()
    {
        $data = Claim::all();
        return view('claim.pdf', compact('data'));
    }
}
