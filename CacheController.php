<?php
// app/Http/Controllers/CacheController.php
namespace App\Http\Controllers;

use App\Models\Cache;
use Illuminate\Http\Request;

class CacheController extends Controller
{
    public function index()
    {
        $caches = Cache::paginate(20);
        return view('cache.index', compact('caches'));
    }
    
    public function create()
    {
        return view('cache.create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'expiration' => 'required|integer'
        ]);
        
        Cache::create($validated);
        return redirect()->route('cache.index')->with('success', 'Cache creado correctamente.');
    }
    
    public function show(Cache $cache)
    {
        return view('cache.show', compact('cache'));
    }
    
    public function edit(Cache $cache)
    {
        return view('cache.edit', compact('cache'));
    }
    
    public function update(Request $request, Cache $cache)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'expiration' => 'required|integer'
        ]);
        
        $cache->update($validated);
        return redirect()->route('cache.index')->with('success', 'Cache actualizado correctamente.');
    }
    
    public function destroy(Cache $cache)
    {
        $cache->delete();
        return redirect()->route('cache.index')->with('success', 'Cache eliminado correctamente.');
    }
}