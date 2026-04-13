<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FailedJobController extends Controller
{
    public function index()
    {
        $failedJobs = DB::table('failed_jobs')->paginate(25);
        return view('failed_jobs.index', compact('failedJobs'));
    }

    public function export()
    {
        // Implementar exportación si es necesario
        return redirect()->back()->with('error', 'Función no implementada');
    }

    public function pdf()
    {
        // Implementar PDF si es necesario
        return redirect()->back()->with('error', 'Función no implementada');
    }
}