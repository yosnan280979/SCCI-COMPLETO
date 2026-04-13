@extends('layouts.app')

@section('title', 'Detalle Relación CVI vs Ctto Sum')

@section('content')
<div class="container-fluid">
    @include('partials.messages')
    
    <div class="card">
        <div class="card-header">
            <h3>Detalle Relación CVI vs Ctto Sum #{{ $item->Id }}</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Información General</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">ID:</th>
                            <td>{{ $item->Id }}</td>
                        </tr>
                        <tr>
                            <th>ID SOE:</th>
                            <td>
                                @if($item->Id_SOE)
                                    {{ $item->Id_SOE }}
                                    @if($item->datosSOE)
                                        <br><small class="text-muted">({{ $item->datosSOE->{'No Suplemento'} ?? 'Sin número' }})</small>
                                    @endif
                                @else
                                    <span class="text-muted">No asignado</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>ID Contrato Suministro:</th>
                            <td>
                                @if($item->Id_Ctto_Sum)
                                    {{ $item->Id_Ctto_Sum }}
                                    @if($item->contratoSuministro)
                                        <br><small class="text-muted">({{ $item->contratoSuministro->{'No Ctto Suministro'} ?? 'Sin número' }})</small>
                                    @endif
                                @else
                                    <span class="text-muted">No asignado</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h5>Información Relacionada</h5>
                    @if($item->datosSOE)
                    <div class="card mb-2">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">Datos SOE</h6>
                        </div>
                        <div class="card-body p-2">
                            <p class="mb-1"><strong>ID:</strong> {{ $item->datosSOE->{'Id SOE'} }}</p>
                            <p class="mb-1"><strong>No. Suplemento:</strong> {{ $item->datosSOE->{'No Suplemento'} ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>No. Acta:</strong> {{ $item->datosSOE->{'No Acta'} ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($item->contratoSuministro)
                    <div class="card">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">Contrato Suministro</h6>
                        </div>
                        <div class="card-body p-2">
                            <p class="mb-1"><strong>ID:</strong> {{ $item->contratoSuministro->{'Id Cttosuministro'} }}</p>
                            <p class="mb-1"><strong>No. Ctto:</strong> {{ $item->contratoSuministro->{'No Ctto Suministro'} ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Descripción:</strong> {{ $item->contratoSuministro->{'Descripcion'} ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="mt-4">
                <a href="{{ route('ctto-cvi-ctto-sum.index') }}" class="btn btn-secondary">Volver</a>
                <a href="{{ route('ctto-cvi-ctto-sum.edit', $item->Id) }}" class="btn btn-warning">Editar</a>
                <form action="{{ route('ctto-cvi-ctto-sum.destroy', $item->Id) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Eliminar esta relación?')">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection