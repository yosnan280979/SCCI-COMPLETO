<?php

namespace App\Http\Controllers;

use App\Models\Dtproperty;
use Illuminate\Http\Request;

class DtpropertyController extends Controller
{
    public function index()
    {
        $dtproperties = Dtproperty::all();
        return view('dtproperties.index', compact('dtproperties'));
    }

    public function create()
    {
        return view('dtproperties.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'property' => 'required',
            'value' => 'nullable',
            'uvalue' => 'nullable',
            'version' => 'required|integer'
        ]);

        Dtproperty::create($request->all());
        return redirect()->route('dtproperties.index')->with('success', 'Dtproperty creado exitosamente.');
    }

    public function show(Dtproperty $dtproperty)
    {
        return view('dtproperties.show', compact('dtproperty'));
    }

    public function edit(Dtproperty $dtproperty)
    {
        return view('dtproperties.edit', compact('dtproperty'));
    }

    public function update(Request $request, Dtproperty $dtproperty)
    {
        $request->validate([
            'property' => 'required',
            'value' => 'nullable',
            'uvalue' => 'nullable',
            'version' => 'required|integer'
        ]);

        $dtproperty->update($request->all());
        return redirect()->route('dtproperties.index')->with('success', 'Dtproperty actualizado exitosamente.');
    }

    public function destroy(Dtproperty $dtproperty)
    {
        $dtproperty->delete();
        return redirect()->route('dtproperties.index')->with('success', 'Dtproperty eliminado exitosamente.');
    }
}