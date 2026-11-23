@extends('layouts.auth')
@section('title', 'Mis constancias')
@section('content')
<h2>Mis constancias digitales</h2>
<p><a href="{{ route('alumno.panel') }}">Volver a mi panel</a> · <a href="{{ route('alumno.historial.index') }}">Mi historial</a></p>
<p class="nota">Se emiten solo para exámenes oficiales aprobados (mínimo 700/1000). Cada resultado genera un único folio y PDF verificable.</p>
@if(session('estado')) <p style="padding:12px;background:#e8fbf0;color:#07572e">{{ session('estado') }}</p> @endif
@if($errors->has('constancia'))<p style="color:#ab1924">{{ $errors->first('constancia') }}</p>@endif
@if($resultados->isEmpty())
    <p>Aún no tienes evaluaciones aprobadas. Puedes consultar los exámenes presentados en tu historial.</p>
@else
    @foreach($resultados as $resultado)
        @php($constancia = $constancias->get($resultado->getKey()))
        <section style="border:1px solid #d5deea;border-radius:10px;padding:18px;margin:15px 0">
            <h3>{{ $resultado->intento->examen?->nombre ?? 'Evaluación de conocimientos' }}</h3>
            <p>Intento #{{ $resultado->intento->numero_intento }} · {{ \App\Support\FechaMexico::mostrar($resultado->fecha_calificacion) }} (CDMX)</p>
            <p><strong>{{ $resultado->calificacion_final }}/1000 · APROBADO</strong></p>
            @if(!$constancia)
                <form method="POST" action="{{ route('alumno.constancias.generar', $resultado->getKey()) }}">
                    @csrf
                    <button type="submit">Generar constancia PDF</button>
                </form>
            @else
                <p><strong>Folio:</strong> {{ $constancia->folio }} · Estado: <strong>{{ strtoupper($constancia->estado) }}</strong></p>
                @if($constancia->estado === 'vigente')
                    <p><a href="{{ route('alumno.constancias.descargar', $constancia->getKey()) }}">Descargar constancia PDF</a></p>
                @else
                    <p>Esta constancia fue revocada. No se puede volver a emitir para el mismo resultado.</p>
                @endif
                <p><a href="{{ route('constancias.verificar.qr', $constancia->clave_verificacion) }}">Consultar autenticidad</a></p>
            @endif
        </section>
    @endforeach
@endif
@endsection
