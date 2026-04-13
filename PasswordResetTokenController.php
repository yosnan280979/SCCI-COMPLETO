<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use Illuminate\Http\Request;

class PasswordResetTokenController extends Controller
{
    public function index()
    {
        $tokens = PasswordResetToken::all();
        return view('password_reset_tokens.index', compact('tokens'));
    }

    public function show(PasswordResetToken $passwordResetToken)
    {
        return view('password_reset_tokens.show', compact('passwordResetToken'));
    }

    public function destroy(PasswordResetToken $passwordResetToken)
    {
        $passwordResetToken->delete();
        return redirect()->route('password_reset_tokens.index')->with('success', 'Token eliminado exitosamente.');
    }
}