<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\GenericExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function excel($model)
    {
        $className = 'App\\Models\\' . ucfirst($model);
        
        if (!class_exists($className)) {
            abort(404, 'Modelo no encontrado');
        }
        
        $filename = strtolower($model) . '_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(new GenericExport($className), $filename);
    }
    
    public function pdf($model)
    {
        $className = 'App\\Models\\' . ucfirst($model);
        
        if (!class_exists($className)) {
            abort(404, 'Modelo no encontrado');
        }
        
        $data = $className::all();
        $filename = strtolower($model) . '_' . date('Ymd_His') . '.pdf';
        
        $pdf = Pdf::loadView('exports.pdf', [
            'data' => $data,
            'model' => $model
        ]);
        
        return $pdf->download($filename);
    }
}