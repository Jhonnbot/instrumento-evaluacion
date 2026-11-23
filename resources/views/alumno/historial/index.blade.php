@extends('layouts.auth')
@section('title', 'Mi historial de evaluaciones')
@section('content')
<h2>Mi historial de evaluaciones</h2>
<p><a href="{{ route('alumno.panel') }}">Volver a mi panel</a> · <a href="{{ route('alumno.examen.index') }}">Examen formal</a></p>
<p class="nota">Solo se muestran resultados de exámenes oficiales concluidos. No se guarda historial del simulador. Horas de Ciudad de México (UTC−6).</p>
<form method="GET" action="{{ route('alumno.historial.index') }}">
    <label for="dictamen">Resultado</label>
    <select id="dictamen" name="dictamen">
        <option value="">Todos</option>
        <option value="APROBADO" @selected(($filtros['dictamen'] ?? '') === 'APROBADO')>Aprobado</option>
        <option value="REPROBADO" @selected(($filtros['dictamen'] ?? '') === 'REPROBADO')>Reprobado</option>
    </select>
    <label for="desde">Desde (fecha local)</label>
    <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}">
    <label for="hasta">Hasta (fecha local)</label>
    <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">
    <p><button type="submit">Filtrar</button> <a href="{{ route('alumno.historial.index') }}">Limpiar filtros</a></p>
</form>
<p>{{ $registros->total() }} evaluación(es) encontrada(s).</p>
@if($registros->isEmpty())
    <p>No existen resultados oficiales para los filtros seleccionados.</p>
@else
    @foreach($registros as $registro)
        <section style="border:1px solid #d5deea; border-radius:8px; padding:16px; margin:12px 0">
            <h3>Intento #{{ $registro->numero_intento }} — {{ $registro->examen?->nombre ?? 'Evaluación' }}</h3>
            <p><strong>Fecha del examen:</strong> {{ \App\Support\FechaMexico::mostrar($registro->fecha_inicio) }} (CDMX)</p>
            <p><strong>Calificación:</strong> {{ $registro->resultado->calificacion_final }} / 1000 · <strong>{{ $registro->resultado->dictamen }}</strong></p>
            <p><strong>Entrega:</strong> {{ $registro->estado === 'tiempo_agotado' ? 'Cerrado por tiempo agotado' : 'Enviado' }}</p>
            <p><a href="{{ route('alumno.historial.detalle', $registro->getKey()) }}">Ver detalle de resultados</a></p>
        </section>
    @endforeach
    {{ $registros->links() }}
@endif
@endsection
