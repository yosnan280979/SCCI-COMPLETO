@extends('layouts.app')

@section('title', 'Exportar CreditLine')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">Exportar CreditLine</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('creditline.export.process') }}" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <label for="format">Formato de Exportación *</label>
                            <select class="form-control @error('format') is-invalid @enderror" id="format" name="format" required>
                                <option value="">Seleccione un formato</option>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                                <option value="pdf">PDF (.pdf)</option>
                            </select>
                            @error('format')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="columns">Columnas a Exportar</label>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="select_all" onchange="toggleSelectAll(this)">
                                        <label class="form-check-label" for="select_all">Seleccionar Todos</label>
                                    </div>
                                </div>
                                                            <div class="form-check col-md-4">
                                <input class="form-check-input column-checkbox" type="checkbox" id="id_column" name="columns[]" value="id" checked>
                                <label class="form-check-label" for="id_column">ID</label>
                            </div>                            <div class="form-check col-md-4">
                                <input class="form-check-input column-checkbox" type="checkbox" id="nombre_column" name="columns[]" value="nombre" checked>
                                <label class="form-check-label" for="nombre_column">Nombre</label>
                            </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Seleccione el formato y opciones de exportación para generar el archivo.
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                        <a href="{{ route('creditline.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        document.getElementById('fecha_fin').value = today.toISOString().split('T')[0];
        document.getElementById('fecha_inicio').value = firstDay.toISOString().split('T')[0];
    });

    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.column-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
        });
    }
</script>
@endpush