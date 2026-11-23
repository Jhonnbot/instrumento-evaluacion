@extends('layouts.auth')
@section('title', 'Historial general de evaluaciones')
@section('content')
<h2>Historial general de evaluaciones</h2>
<p><a href="{{ route('coordinador.panel') }}">Volver al panel del Coordinador</a></p>
<p class="nota">Resultados oficiales guardados en MySQL. Las fechas se muestran y filtran en horario de Ciudad de México (UTC−6).</p>
<form method="GET" action="{{ route('coordinador.historial.index') }}">
    <label for="buscar">Alumno (nombre, correo o ID)</label>
    <input id="buscar" type="text" name="buscar" maxlength="150" placeholder="Buscar alumno" value="{{ $filtros['buscar'] ?? '' }}">
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
    <p><button type="submit">Filtrar</button> <a href="{{ route('coordinador.historial.index') }}">Limpiar filtros</a></p>
</form>
<p>{{ $registros->total() }} evaluación(es) encontrada(s).</p>
@if($registros->isEmpty())
    <p>No existen resultados oficiales para los filtros seleccionados.</p>
@else
    @foreach($registros as $registro)
        <section style="border:1px solid #d5deea; border-radius:8px; padding:16px; margin:12px 0">
            <h3>{{ $registro->alumno?->nombre_completo ?? 'Alumno no disponible' }} (ID: {{ $registro->id_usuario }})</h3>
            <p><strong>Correo:</strong> {{ $registro->alumno?->correo ?? '—' }}</p>
            <p><strong>Evaluación:</strong> {{ $registro->examen?->nombre ?? 'Evaluación' }} · Intento #{{ $registro->numero_intento }}</p>
            <p><strong>Fecha:</strong> {{ \App\Support\FechaMexico::mostrar($registro->fecha_inicio) }} (CDMX)</p>
            <p><strong>Calificación:</strong> {{ $registro->resultado->calificacion_final }} / 1000 · <strong>{{ $registro->resultado->dictamen }}</strong></p>
            <p><a href="{{ route('coordinador.historial.detalle', $registro->getKey()) }}">Ver detalle</a></p>
        </section>
    @endforeach
    {{ $registros->links() }}
@endif
@endsection
