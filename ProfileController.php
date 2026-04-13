<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NomencladorTiposUsuarios as UserType;
use App\Models\Area;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function index()
    {
        $user = auth()->user();
        $userTypes = UserType::all();
        $areas = Area::all();
        $specialists = Specialist::all();
        
        return view('profile.index', compact('user', 'userTypes', 'areas', 'specialists'));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = auth()->user();
        $userTypes = UserType::all();
        $areas = Area::all();
        $specialists = Specialist::all();
        
        return view('profile.edit', compact('user', 'userTypes', 'areas', 'specialists'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'Usuario' => [
                'required',
                Rule::unique('Nomenclador Usuarios')->ignore($user->Usuario, 'Usuario'),
                'max:50'
            ],
            'PWD' => 'nullable|min:6',
            'current_password' => 'required_with:PWD',
            'Id Tipo Usuario' => 'required|exists:Nomenclador Tipos Usuarios,Id Tipo Usuario',
            'Id Area' => 'nullable|exists:Nomenclador Areas,Id Area',
            'Id Especialista' => 'nullable|exists:Nomenclador Especialistas,Id Especialista',
            'Nombre completo' => 'required|max:100',
            'Activo' => 'boolean',
        ]);

        // Verificar contraseña actual si se proporciona una nueva
        if ($request->filled('PWD')) {
            if (!Hash::check($request->current_password, $user->PWD)) {
                return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.'])
                    ->withInput();
            }
            $validated['PWD'] = Hash::make($validated['PWD']);
        } else {
            unset($validated['PWD']);
        }

        unset($validated['current_password']);
        
        $user->update($validated);

        return redirect()->route('profile.index')
            ->with('success', 'Perfil actualizado exitosamente.');
    }

    /**
     * Display the user's profile (show).
     */
    public function show()
    {
        $user = auth()->user();
        
        return view('profile.show', compact('user'));
    }
}