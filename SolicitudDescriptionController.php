<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SolicitudDescription;

class SolicitudDescriptionController extends Controller
{
    public function index()
    {
        $items = SolicitudDescription::all();
        return view('SolicitudDescription.index', compact('items'));
    }

    public function create()
    {
        return view('SolicitudDescription.create');
    }

    public function store(Request $request)
    {
        // Validación y creación
        return redirect()->route('SolicitudDescription.index');
    }

    public function show($id)
    {
        $item = SolicitudDescription::findOrFail($id);
        return view('SolicitudDescription.show', compact('item'));
    }

    public function edit($id)
    {
        $item = SolicitudDescription::findOrFail($id);
        return view('SolicitudDescription.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        // Validación y actualización
        return redirect()->route('SolicitudDescription.show', $id);
    }

    public function destroy($id)
    {
        $item = SolicitudDescription::findOrFail($id);
        $item->delete();
        return redirect()->route('SolicitudDescription.index');
    }
}
