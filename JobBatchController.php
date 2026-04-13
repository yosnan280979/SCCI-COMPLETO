<?php

namespace App\Http\Controllers;

use App\Models\JobBatch;
use Illuminate\Http\Request;

class JobBatchController extends Controller
{
    public function index()
    {
        $jobBatches = JobBatch::all();
        return view('job_batches.index', compact('jobBatches'));
    }

    public function show(JobBatch $jobBatch)
    {
        return view('job_batches.show', compact('jobBatch'));
    }

    public function destroy(JobBatch $jobBatch)
    {
        $jobBatch->delete();
        return redirect()->route('job_batches.index')->with('success', 'Job Batch eliminado exitosamente.');
    }
}