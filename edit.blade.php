@extends('layouts.app')

@section('title', 'Editar Relación CVI vs Ctto Sum')

@section('content')
<div class="container-fluid">
    @include('partials.messages')
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Relación CVI vs Ctto Sum #{{ $item->Id }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('ctto-cvi-ctto-sum.update', $item->Id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="Id_SOE" class="form-label">ID SOE</label>
                        <select class="form-select" id="Id_SOE" name="Id_SOE">
                            <option value="">Seleccionar SOE</option>
                            @foreach($soes as $soe)
                                <option value="{{ $soe->{'Id SOE'} }}" {{ $item->Id_SOE == $soe->{'Id SOE'} ? 'selected' : '' }}>
                                    #{{ $soe->{'Id SOE'} }} - {{ $soe->{'No Suplemento'} ?? 'Sin número' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Opcional</small>
                    </div>
                    <div class="col-md-6">
                        <label for="Id_Ctto_Sum" class="form-label">ID Contrato Suministro</label>
                        <select class="form-select" id="Id_Ctto_Sum" name="Id_Ctto_Sum">
                            <option value="">Seleccionar Contrato Suministro</option>
                            @foreach($contratos as $contrato)
                                <option value="{{ $contrato->{'Id Cttosuministro'} }}" {{ $item->Id_Ctto_Sum == $contrato->{'Id Cttosuministro'} ? 'selected' : '' }}>
                                    #{{ $contrato->{'Id Cttosuministro'} }} - {{ $contrato->{'No Ctto Suministro'} ?? 'Sin número' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Opcional</small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="{{ route('ctto-cvi-ctto-sum.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Relación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection