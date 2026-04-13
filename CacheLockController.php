<?php
// app/Http/Controllers/CacheLockController.php
namespace App\Http\Controllers;

use App\Models\CacheLock;
use Illuminate\Http\Request;

class CacheLockController extends Controller
{
    public function index()
    {
        $cacheLocks = CacheLock::paginate(20);
        return view('cache_locks.index', compact('cacheLocks'));
    }
    
    public function create()
    {
        return view('cache_locks.create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'expiration' => 'required|integer'
        ]);
        
        CacheLock::create($validated);
        return redirect()->route('cache_locks.index')->with('success', 'Cache Lock creado correctamente.');
    }
    
    public function show(CacheLock $cacheLock)
    {
        return view('cache_locks.show', compact('cacheLock'));
    }
    
    public function edit(CacheLock $cacheLock)
    {
        return view('cache_locks.edit', compact('cacheLock'));
    }
    
    public function update(Request $request, CacheLock $cacheLock)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'expiration' => 'required|integer'
        ]);
        
        $cacheLock->update($validated);
        return redirect()->route('cache_locks.index')->with('success', 'Cache Lock actualizado correctamente.');
    }
    
    public function destroy(CacheLock $cacheLock)
    {
        $cacheLock->delete();
        return redirect()->route('cache_locks.index')->with('success', 'Cache Lock eliminado correctamente.');
    }
}