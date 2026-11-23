@extends('layouts.auth')
@section('title','Simulador de práctica')
@section('content')
<h2>Simulador de práctica</h2>
<p><a href="{{ route('alumno.panel') }}">Volver a mi panel</a></p>
<p>Podrás realizar sesiones ilimitadas durante el periodo habilitado de 14 días.</p>
<p>En cada sesión se seleccionan <strong>{{ $cantidad }} preguntas aleatorias</strong>, de dos en dos.</p>
<p class="nota">El resultado es formativo. No se guarda como historial ni consume intentos del examen formal.</p>
<p>Inicio: {{ \App\Support\FechaMexico::mostrar($ventana['inicio']) }}</p>
<p>Fin: {{ \App\Support\FechaMexico::mostrar($ventana['fin']) }}</p>
@if(session('aviso')) <p class="errores" role="alert">{{session('aviso')}}</p> @endif
@if($ventana['disponible'])
    <p><strong>Práctica disponible.</strong></p>
    @if($enCurso)
        <p><a href="{{ route('alumno.simulador.preguntas',1) }}">Continuar práctica actual</a></p>
        <p class="nota">Si inicias otra práctica, se sustituye la sesión anterior.</p>
    @endif
    <form method="POST" action="{{ route('alumno.simulador.iniciar') }}">
        @csrf <button type="submit">{{ $enCurso ? 'Iniciar otra práctica' : 'Iniciar práctica' }}</button>
    </form>
@else
    <p class="errores">Fuera del periodo habilitado de dos semanas.</p>
@endif
@endsection
