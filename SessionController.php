<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::with('user')->get();
        return view('sessions.index', compact('sessions'));
    }

    public function show(Session $session)
    {
        $session->load('user');
        return view('sessions.show', compact('session'));
    }

    public function destroy(Session $session)
    {
        $session->delete();
        return redirect()->route('sessions.index')->with('success', 'Sesión eliminada exitosamente.');
    }
}