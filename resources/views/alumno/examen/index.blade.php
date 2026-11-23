@extends('layouts.auth')
@section('title','Examen formal')
@section('content')
<h2>Examen formal</h2>
<p><a href="{{route('alumno.panel')}}">Volver a mi panel</a></p>
@if(session('aviso'))<p class="errores">{{session('aviso')}}</p>@endif
@if(!$examen)
    <p>El examen formal todavía no está configurado. El Coordinador debe preparar el examen.</p>
@else
    <p><strong>{{ $examen->nombre }}</strong></p>
    <p>Duración: 50 minutos, sin pausa. Se muestran dos preguntas por pantalla y la calificación oficial va de 0 a 1,000 (aprobación desde 700).</p>
    <p class="nota">La evaluación requiere cinco categorías activas, con {{ $porCategoria }} preguntas por categoría y 20 % de peso cada una.</p>
    <p>Intentos utilizados: <strong>{{ $estado['usados'] }}</strong>. Intentos iniciales: 2.</p>
    @if($estado['aprobado'])
        <p class="exito">Ya aprobaste. No puedes iniciar otro examen.</p>
    @elseif($estado['activo'])
        <p>Tienes un intento en curso. Su temporizador continúa aunque cierres el navegador.</p>
        <p><a href="{{route('alumno.examen.preguntas',[$estado['activo']->getKey(),1])}}">Continuar examen</a></p>
    @elseif($estado['requiere_autorizacion'])
        <p class="errores">Ya utilizaste tus dos intentos. Necesitas una oportunidad adicional autorizada por el Coordinador.</p>
        <p><a href="{{ route('alumno.solicitudes.index') }}">Solicitar oportunidad adicional</a></p>
    @elseif($estado['puede_iniciar'])
        <form method="POST" action="{{route('alumno.examen.iniciar')}}">
            @csrf
            <button type="submit" onclick="return confirm('¿Iniciar examen formal? El tiempo empezará y no se podrá pausar.')">Iniciar examen formal</button>
        </form>
    @endif
    @if($estado['intentos']->isNotEmpty())
        <h3>Intentos presentados</h3>
        @foreach($estado['intentos'] as $i)
            <p>Intento #{{ $i->numero_intento }} — {{ str_replace('_',' ', $i->estado) }}
                @if($i->estado!=='en_curso') · <a href="{{route('alumno.examen.resultado',$i->getKey())}}">Ver resultado</a> @endif
            </p>
        @endforeach
    @endif
@endif
@endsection
