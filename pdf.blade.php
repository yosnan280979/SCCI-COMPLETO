<!DOCTYPE html>
<html>
<head>
    <title>Ctto CVI vs Ctto Sum - PDF</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 14px; color: #666; }
        .filters { margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: left; padding: 8px; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #666; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Relaciones CVI vs Contrato Suministro</div>
        <div class="subtitle">Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
    
    @if(!empty($filtros))
    <div class="filters">
        <strong>Filtros aplicados:</strong><br>
        @foreach($filtros as $filtro)
        • {{ $filtro }}<br>
        @endforeach
    </div>
    @endif
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ID SOE</th>
                <th>No Suplemento</th>
                <th>ID Ctto Sum</th>
                <th>No Ctto Suministro</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ $item->Id }}</td>
                <td>{{ $item->Id_SOE ?? 'N/A' }}</td>
                <td>{{ $item->datosSOE?->{'No Suplemento'} ?? 'N/A' }}</td>
                <td>{{ $item->Id_Ctto_Sum ?? 'N/A' }}</td>
                <td>{{ $item->contratoSuministro?->{'No Ctto Suministro'} ?? 'N/A' }}</td>
                <td>{{ Str::limit($item->contratoSuministro?->{'Descripcion'} ?? '', 50) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No hay datos para mostrar</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        Total de registros: {{ $items->count() }}<br>
        Generado por: {{ auth()->user()->{'Usuario'} ?? 'Sistema' }}
    </div>
</body>
</html>