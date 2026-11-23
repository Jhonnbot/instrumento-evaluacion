@extends('layouts.auth')
@section('title', 'Constancias emitidas')
@section('content')
<h2>Constancias digitales emitidas</h2>
<p><a href="{{ route('coordinador.panel') }}">Volver al panel del Coordinador</a></p>
<p class="nota">Consulta los folios emitidos y revoca documentos que no deban seguir verificándose como vigentes.</p>
@if(session('estado')) <p style="padding:12px;background:#e8fbf0;color:#07572e">{{ session('estado') }}</p> @endif
<form method="GET" action="{{ route('coordinador.constancias.index') }}">
    <label>Buscar por folio, nombre o correo<br><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o folio"></label>
    <label>Estado<br><select name="estado">
        <option value="">Todos</option>
        <option value="vigente" @selected(request('estado') === 'vigente')>Vigentes</option>
        <option value="revocada" @selected(request('estado') === 'revocada')>Revocadas</option>
    </select></label>
    <button type="submit">Filtrar</button>
</form>
<p>{{ $constancias->total() }} constancia(s) encontrada(s).</p>
@forelse($constancias as $constancia)
    <section style="border:1px solid #d5deea;border-radius:10px;padding:16px;margin:14px 0">
        <h3>{{ $constancia->folio }} — {{ strtoupper($constancia->estado) }}</h3>
        <p><strong>Alumno:</strong> {{ $constancia->resultado->intento->alumno?->nombre_completo }}</p>
        <p>Resultado: {{ $constancia->resultado->calificacion_final }}/1000 · Fecha: {{ \App\Support\FechaMexico::mostrar($constancia->fecha_emision) }} (CDMX)</p>
        <p><a href="{{ route('constancias.verificar.qr', $constancia->clave_verificacion) }}">Verificar públicamente</a></p>
        @if($constancia->estado === 'vigente')
            <form method="POST" action="{{ route('coordinador.constancias.revocar', $constancia->getKey()) }}" onsubmit="return confirm('¿Revocar definitivamente este folio?')">
                @csrf
                <button type="submit">Revocar constancia</button>
            </form>
        @endif
    </section>
@empty
    <p>Aún no hay constancias emitidas.</p>
@endforelse
{{ $constancias->links() }}
@endsection
