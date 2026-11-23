@extends('layouts.auth')
@section('title','Solicitudes de intentos')
@section('content')
<h2>Revisión de oportunidades adicionales</h2>
<p class="nota">Horas en Ciudad de México (UTC−6).</p>
<p><a href="{{route('coordinador.panel')}}">Volver al panel del Coordinador</a></p>
@if(session('exito'))<p class="exito">{{session('exito')}}</p>@endif
@if($errors->any())<div class="errores"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="GET" action="{{ route('coordinador.solicitudes.index') }}">
    <label for="estado">Filtrar solicitudes</label>
    <select name="estado" id="estado">
        <option value="">Todas</option>
        @foreach(['pendiente'=>'Pendientes','aprobada'=>'Aprobadas','rechazada'=>'Rechazadas'] as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
        @endforeach
    </select>
    <button type="submit">Filtrar</button>
</form>
<p>{{ $solicitudes->total() }} solicitud(es) encontradas.</p>
@if($solicitudes->isEmpty())
    <p>No se encontraron solicitudes.</p>
@else
    @foreach($solicitudes as $solicitud)
        <section style="border:1px solid #d5deea; border-radius:8px; padding:16px; margin:12px 0">
            <h3>Solicitud #{{ $solicitud->id_solicitud }} — {{ ucfirst($solicitud->estado) }}</h3>
            <p><strong>Alumno:</strong> {{ $solicitud->alumno?->nombre_completo ?? 'Cuenta no disponible' }}
                (ID: {{ $solicitud->id_usuario }})</p>
            <p><strong>Correo:</strong> {{ $solicitud->alumno?->correo ?? '—' }}</p>
            <p><strong>Fecha:</strong> {{ \App\Support\FechaMexico::mostrar($solicitud->fecha_solicitud) }}</p>
            @if($solicitud->estado === 'pendiente')
                <form method="POST" action="{{route('coordinador.solicitudes.resolver',$solicitud->id_solicitud)}}">
                    @csrf
                    <label for="obs-{{ $solicitud->id_solicitud }}">Observaciones (opcionales)</label>
                    <textarea name="observaciones" id="obs-{{ $solicitud->id_solicitud }}" maxlength="2000" rows="3"></textarea>
                    <div style="display:flex; gap:8px; margin-top:10px">
                        <button name="decision" value="aprobar" type="submit" onclick="return confirm('¿Autorizar un intento adicional para este alumno?')">Aprobar</button>
                        <button name="decision" value="rechazar" type="submit" onclick="return confirm('¿Rechazar esta solicitud?')">Rechazar</button>
                    </div>
                </form>
            @else
                <p><strong>Coordinador:</strong> {{ $solicitud->coordinador?->nombre_completo ?? '—' }} ·
                    {{ \App\Support\FechaMexico::mostrar($solicitud->fecha_resolucion) ?? 'Sin fecha' }}</p>
                @if($solicitud->observaciones)<p>Observaciones: {{ $solicitud->observaciones }}</p>@endif
                @if($solicitud->estado === 'aprobada')
                    <p>{{ $solicitud->intento ? 'Utilizada en el intento #'.$solicitud->intento->numero_intento : 'Aprobación todavía sin utilizar' }}</p>
                @endif
            @endif
        </section>
    @endforeach
    {{ $solicitudes->links() }}
@endif
@endsection
