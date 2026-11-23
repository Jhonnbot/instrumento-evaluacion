@extends('layouts.auth')
@section('title', 'Gestión de alumnos')
@section('content')
    <h2>Gestión de alumnos</h2>
    <p><a href="{{ route('coordinador.panel') }}">Volver al panel del coordinador</a></p>
    <p class="nota">Directorio de alumnos registrados. Solo el Coordinador puede editar o desactivar sus cuentas.</p>

    <form method="GET" action="{{ route('coordinador.alumnos.index') }}" style="display:flex; gap:.5rem; align-items:end; flex-wrap:wrap; margin-bottom:1.5rem;">
        <div style="flex:1; min-width:220px;">
            <label for="buscar">Nombre, correo, CURP o ID</label>
            <input id="buscar" name="buscar" value="{{ $busqueda }}" maxlength="150" placeholder="Buscar alumno">
        </div>
        <button type="submit">Buscar</button>
        @if ($busqueda !== '') <a href="{{ route('coordinador.alumnos.index') }}">Limpiar</a> @endif
    </form>

    <p class="nota">{{ $alumnos->total() }} alumno(s) encontrado(s).</p>

    @forelse ($alumnos as $alumno)
        <section style="border:1px solid #d9dfe7; border-radius:8px; padding:1rem; margin:1rem 0;">
            <p><strong>#{{ $alumno->id_usuario }} — {{ $alumno->nombre_completo }}</strong></p>
            <p>Correo: {{ $alumno->correo }}<br>
               Teléfono: {{ $alumno->telefono }}<br>
               CURP: {{ $alumno->curp }}<br>
               Estado: <strong>{{ ucfirst($alumno->estado) }}</strong></p>
            <p><a href="{{ route('coordinador.alumnos.edit', $alumno->id_usuario) }}">Editar datos</a></p>
            <form method="POST" action="{{ route('coordinador.alumnos.estado', $alumno->id_usuario) }}"
                  onsubmit="return confirm('¿Confirmas que deseas cambiar el estado de esta cuenta?');">
                @csrf
                @method('PATCH')
                <input type="hidden" name="estado" value="{{ $alumno->estado === 'activo' ? 'inactivo' : 'activo' }}">
                <button type="submit">{{ $alumno->estado === 'activo' ? 'Desactivar cuenta' : 'Activar cuenta' }}</button>
            </form>
        </section>
    @empty
        <p>No hay alumnos que coincidan con la búsqueda.</p>
    @endforelse

    @if ($alumnos->hasPages())
        <nav aria-label="Paginación" style="display:flex; gap:1rem; justify-content:space-between; margin-top:1rem;">
            @if ($alumnos->previousPageUrl())
                <a href="{{ $alumnos->previousPageUrl() }}">Anterior</a>
            @else
                <span class="nota">Anterior</span>
            @endif
            <span>Página {{ $alumnos->currentPage() }} de {{ $alumnos->lastPage() }}</span>
            @if ($alumnos->nextPageUrl())
                <a href="{{ $alumnos->nextPageUrl() }}">Siguiente</a>
            @else
                <span class="nota">Siguiente</span>
            @endif
        </nav>
    @endif
@endsection
