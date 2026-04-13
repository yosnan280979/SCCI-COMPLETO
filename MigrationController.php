<?php

namespace App\Http\Controllers;

use App\Models\Migration;
use Illuminate\Http\Request;

class MigrationController extends Controller
{
    public function index()
    {
        $migrations = Migration::all();
        return view('migrations.index', compact('migrations'));
    }

    public function show(Migration $migration)
    {
        return view('migrations.show', compact('migration'));
    }
}