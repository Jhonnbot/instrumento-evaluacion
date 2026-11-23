@extends('layouts.auth')
@section('title', 'Detalle del resultado oficial')
@section('content')
<h2>Detalle de evaluación oficial</h2>
<p><a href="{{ route($modo === 'coordinador' ? 'coordinador.historial.index' : 'alumno.historial.index') }}">Volver al historial</a></p>
<p class="nota">Fechas en horario de Ciudad de México (UTC−6). Los puntajes corresponden al examen oficial, no al simulador.</p>
<h3>{{ $registro->examen?->nombre ?? 'Evaluación de conocimientos' }}</h3>
@if($modo === 'coordinador')
    <p><strong>Alumno:</strong> {{ $registro->alumno?->nombre_completo }} (ID {{ $registro->id_usuario }}) · {{ $registro->alumno?->correo }}</p>
@endif
<p><strong>Intento:</strong> #{{ $registro->numero_intento }} · {{ $registro->estado === 'tiempo_agotado' ? 'Cerrado por tiempo agotado' : 'Enviado' }}</p>
<p><strong>Inicio:</strong> {{ \App\Support\FechaMexico::mostrar($registro->fecha_inicio) }} · <strong>Fin:</strong> {{ \App\Support\FechaMexico::mostrar($registro->fecha_fin) }} (CDMX)</p>
<p><strong>Calificación:</strong> <span style="font-size:1.6rem"><strong>{{ $registro->resultado->calificacion_final }} / 1000</strong></span></p>
<p><strong>Dictamen:</strong> {{ $registro->resultado->dictamen }} · Mínimo aprobatorio: 700</p>
<p><strong>Calificado:</strong> {{ \App\Support\FechaMexico::mostrar($registro->resultado->fecha_calificacion) }} (CDMX)</p>
<h3>Desglose por categoría (20 % cada una)</h3>
@if($categorias->isEmpty())
    <p>No se encontró un desglose por categoría para este resultado.</p>
@else
    @foreach($categorias as $categoria)
        <p>{{ $categoria->nombre }}: <strong>{{ number_format((float) $categoria->puntaje_obtenido, 2) }} / 200</strong></p>
    @endforeach
@endif
<h3>Desglose por tema</h3>
@if($temas->isEmpty())
    <p>No se encontró un desglose por tema para este resultado.</p>
@else
    @foreach($temas as $tema)
        <section style="border:1px solid #d5deea; border-radius:8px; padding:12px; margin:8px 0">
            <strong>{{ $tema->categoria }} / {{ $tema->tema }}</strong>
            <p>Aciertos: {{ $tema->respuestas_correctas }} de {{ $tema->preguntas_totales }} pregunta(s) · {{ $tema->preguntas_totales > 0 ? number_format(100 * $tema->respuestas_correctas / $tema->preguntas_totales, 2) : '0.00' }} %</p>
        </section>
    @endforeach
@endif
@if($modo === 'alumno' && $registro->resultado->dictamen === 'APROBADO')
    <p><a href="{{ route('alumno.constancias.index') }}">Emitir o descargar mi constancia de evaluación</a></p>
@endif
@if($modo === 'alumno')
    <p><a href="{{ route('alumno.examen.resultado', $registro->getKey()) }}">Ver resultado original del examen</a></p>
@endif
@endsection
