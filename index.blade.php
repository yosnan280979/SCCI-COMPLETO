@extends('layouts.app')

@section('title', 'Ctto CVI vs Ctto Sum')

@section('content')
<div class="container-fluid">
    @include('partials.messages')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Listado de Ctto CVI vs Ctto Sum</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('ctto-cvi-ctto-sum.create') }}" class="btn btn-primary btn-sm">➕ Nuevo</a>
                <!-- Formulario para exportaciones (GET) -->
                <form id="exportForm" method="GET" action="{{ route('ctto-cvi-ctto-sum.export.excel') }}" style="display: inline">
                    @csrf
                    <input type="hidden" name="selected" id="exportSelected" value="">
                    <input type="hidden" name="Id_SOE" value="{{ request('Id_SOE') }}">
                    <input type="hidden" name="Id_Ctto_Sum" value="{{ request('Id_Ctto_Sum') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-success btn-sm">📊 Excel</button>
                </form>
                <form id="exportPdfForm" method="GET" action="{{ route('ctto-cvi-ctto-sum.export.pdf') }}" style="display: inline">
                    @csrf
                    <input type="hidden" name="selected" id="exportPdfSelected" value="">
                    <input type="hidden" name="Id_SOE" value="{{ request('Id_SOE') }}">
                    <input type="hidden" name="Id_Ctto_Sum" value="{{ request('Id_Ctto_Sum') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-danger btn-sm">📄 PDF</button>
                </form>
                <form id="printForm" method="GET" action="{{ route('ctto-cvi-ctto-sum.print') }}" style="display: inline" target="_blank">
                    @csrf
                    <input type="hidden" name="selected" id="printSelected" value="">
                    <input type="hidden" name="Id_SOE" value="{{ request('Id_SOE') }}">
                    <input type="hidden" name="Id_Ctto_Sum" value="{{ request('Id_Ctto_Sum') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-secondary btn-sm">🖨️ Imprimir</button>
                </form>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" action="{{ route('ctto-cvi-ctto-sum.index') }}" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Buscar (ID SOE, ID Ctto Sum...)" 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="Id_SOE" value="{{ request('Id_SOE') }}" class="form-control" placeholder="ID SOE">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="Id_Ctto_Sum" value="{{ request('Id_Ctto_Sum') }}" class="form-control" placeholder="ID Ctto Sum">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        <a href="{{ route('ctto-cvi-ctto-sum.index') }}" class="btn btn-secondary w-100">Limpiar</a>
                    </div>
                </div>
            </form>

            @if($items->isEmpty())
                <div class="alert alert-info">No hay registros.</div>
            @else
                <!-- Formulario para eliminación múltiple (DELETE) -->
                <form id="deleteForm" method="POST" action="{{ route('ctto-cvi-ctto-sum.destroy.multiple') }}">
                    @csrf
                    @method('DELETE')
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>
                                        <a href="{{ route('ctto-cvi-ctto-sum.index', array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'Id', 'sort_dir' => request('sort_by') == 'Id' && request('sort_dir', 'desc') == 'desc' ? 'asc' : 'desc'])) }}">
                                            ID
                                            @if(request('sort_by') == 'Id')
                                                @if(request('sort_dir', 'desc') == 'desc')
                                                    <i class="fas fa-sort-down"></i>
                                                @else
                                                    <i class="fas fa-sort-up"></i>
                                                @endif
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('ctto-cvi-ctto-sum.index', array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'Id_SOE', 'sort_dir' => request('sort_by') == 'Id_SOE' && request('sort_dir', 'desc') == 'desc' ? 'asc' : 'desc'])) }}">
                                            ID SOE
                                            @if(request('sort_by') == 'Id_SOE')
                                                @if(request('sort_dir', 'desc') == 'desc')
                                                    <i class="fas fa-sort-down"></i>
                                                @else
                                                    <i class="fas fa-sort-up"></i>
                                                @endif
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('ctto-cvi-ctto-sum.index', array_merge(request()->except(['sort_by', 'sort_dir']), ['sort_by' => 'Id_Ctto_Sum', 'sort_dir' => request('sort_by') == 'Id_Ctto_Sum' && request('sort_dir', 'desc') == 'desc' ? 'asc' : 'desc'])) }}">
                                            ID Ctto Sum
                                            @if(request('sort_by') == 'Id_Ctto_Sum')
                                                @if(request('sort_dir', 'desc') == 'desc')
                                                    <i class="fas fa-sort-down"></i>
                                                @else
                                                    <i class="fas fa-sort-up"></i>
                                                @endif
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="ids[]" value="{{ $item->Id ?? $item->id }}" class="select-item">
                                        </td>
                                        <td>{{ $item->Id ?? $item->id }}</td>
                                        <td>{{ $item->Id_SOE }}</td>
                                        <td>{{ $item->Id_Ctto_Sum }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('ctto-cvi-ctto-sum.show', $item->Id ?? $item->id) }}" class="btn btn-info btn-sm">Ver</a>
                                                <a href="{{ route('ctto-cvi-ctto-sum.edit', $item->Id ?? $item->id) }}" class="btn btn-warning btn-sm">Editar</a>
                                                <form action="{{ route('ctto-cvi-ctto-sum.destroy', $item->Id ?? $item->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este registro?')">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if($items->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>{{ $items->count() }} registros</strong></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    
                    <!-- Botón para eliminar múltiple -->
                    <button type="submit" class="btn btn-danger btn-sm mt-2" onclick="return confirm('¿Eliminar los registros seleccionados?')">
                        🗑️ Eliminar Seleccionados
                    </button>
                </form>

                <!-- Paginación -->
                @if($items->hasPages())
                    @php 
                        $queryParams = request()->except('page');
                        $queryString = http_build_query($queryParams);
                    @endphp
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }} registros
                        </div>
                        <nav>
                            <ul class="pagination mb-0">
                                <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $items->url(1) }}?{{ $queryString }}">« Primera</a>
                                </li>
                                <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $items->previousPageUrl() }}?{{ $queryString }}">‹</a>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link">Página {{ $items->currentPage() }} de {{ $items->lastPage() }}</span>
                                </li>
                                <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $items->nextPageUrl() }}?{{ $queryString }}">›</a>
                                </li>
                                <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $items->url($items->lastPage()) }}?{{ $queryString }}">Última »</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar/deseleccionar todos
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('click', function(e) {
            document.querySelectorAll('.select-item').forEach(cb => cb.checked = e.target.checked);
            updateExportSelected();
        });
    }
    
    // Actualizar exportSelected cuando cambian las selecciones
    document.querySelectorAll('.select-item').forEach(cb => {
        cb.addEventListener('change', updateExportSelected);
    });
    
    function updateExportSelected() {
        const selected = [];
        document.querySelectorAll('.select-item:checked').forEach(cb => {
            selected.push(cb.value);
        });
        
        const selectedString = selected.join(',');
        
        // Actualizar todos los formularios de exportación
        document.getElementById('exportSelected').value = selectedString;
        document.getElementById('exportPdfSelected').value = selectedString;
        document.getElementById('printSelected').value = selectedString;
    }
    
    // Inicializar
    updateExportSelected();
});
</script>
@endsection