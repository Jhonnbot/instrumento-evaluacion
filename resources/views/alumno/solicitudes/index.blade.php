@extends('layouts.auth')
@section('title','Mis solicitudes de intentos')
@section('content')
<h2>Solicitudes de oportunidades adicionales</h2>
<p><a href="{{ route('alumno.examen.index') }}">Volver al examen formal</a> · <a href="{{ route('alumno.panel') }}">Mi panel</a></p>
@if(session('exito'))<p class="exito">{{ session('exito') }}</p>@endif
@if($errors->any())<div class="errores"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p class="nota">Cada solicitud aprobada habilita <strong>una</strong> oportunidad adicional. Las solicitudes pendientes o rechazadas no permiten presentar el examen.</p>
@if(!$estado['examen'])
    <p>No hay un examen formal habilitado.</p>
@elseif($estado['estadoExamen']['aprobado'])
    <p class="exito">Ya aprobaste el examen. No necesitas más intentos.</p>
@elseif($estado['pendiente'])
    <p>Ya tienes una solicitud pendiente. Espera la revisión del Coordinador.</p>
@elseif($estado['estadoExamen']['puede_iniciar'])
    <p>Tienes un intento disponible. <a href="{{route('alumno.examen.index')}}">Puedes acceder al examen</a>.</p>
@elseif($estado['puedeSolicitar'])
    <form method="POST" action="{{route('alumno.solicitudes.store')}}">
        @csrf
        <button type="submit">Solicitar oportunidad adicional</button>
    </form>
@else
    <p>La solicitud estará disponible cuando agotes tus intentos sin aprobar y no tengas oportunidades habilitadas.</p>
@endif
<p class="nota">Horas en Ciudad de México (UTC−6).</p>
<h3>Historial de solicitudes</h3>
@if($solicitudes->isEmpty())
    <p>Aún no has enviado solicitudes.</p>
@else
    @foreach($solicitudes as $solicitud)
        <section style="border:1px solid #d5deea; border-radius:8px; padding:16px; margin:12px 0">
            <strong>Solicitud #{{ $solicitud->id_solicitud }}</strong> · {{ ucfirst($solicitud->estado) }}
            <p>Fecha: {{ \App\Support\FechaMexico::mostrar($solicitud->fecha_solicitud) }}</p>
            @if($solicitud->fecha_resolucion)
                <p>Resuelta: {{ \App\Support\FechaMexico::mostrar($solicitud->fecha_resolucion) }}
                    @if($solicitud->coordinador) · Coordinador: {{ $solicitud->coordinador->nombre_completo }} @endif
                </p>
            @endif
            @if($solicitud->observaciones)<p>Observaciones: {{ $solicitud->observaciones }}</p>@endif
            @if($solicitud->estado === 'aprobada')
                <p>{{ $solicitud->intento ? 'Autorización utilizada en el intento #'.$solicitud->intento->numero_intento : 'Autorización disponible para el siguiente intento.' }}</p>
            @endif
        </section>
    @endforeach
    {{ $solicitudes->links() }}
@endif
@endsection
