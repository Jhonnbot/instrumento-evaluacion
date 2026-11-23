@extends('layouts.auth')
@section('title', 'Banco de preguntas')
@section('content')
    <h2>Banco de preguntas</h2>
    <p><a href="{{ route('coordinador.panel') }}">Panel del coordinador</a> ·
        <a href="{{ route('coordinador.banco.categorias.index') }}">Categorías</a> ·
        <a href="{{ route('coordinador.banco.temas.index') }}">Temas</a></p>
    <p class="nota">Cuatro tipos de reactivos. Las preguntas ya vinculadas a un examen no se editan para preservar la integridad del historial.</p>
    <p><a href="{{ route('coordinador.banco.preguntas.create') }}">+ Crear pregunta</a></p>
    <p><a href="{{ route('coordinador.banco.importacion.index') }}">Importar preguntas masivamente (CSV)</a></p>

    <form method="GET" action="{{ route('coordinador.banco.index') }}" style="background:#f7f9fb;padding:1rem;border-radius:8px;">
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria" style="width:100%;padding:.5rem;">
            <option value="">Todas</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat->id_categoria }}" @selected((string) ($filtros['categoria'] ?? '') === (string) $cat->id_categoria)>{{ $cat->nombre }}</option>
            @endforeach
        </select>
        <label for="tema">Tema</label>
        <select id="tema" name="tema" style="width:100%;padding:.5rem;">
            <option value="">Todos</option>
            @foreach ($temas as $tema)
                <option value="{{ $tema->id_tema }}" @selected((string) ($filtros['tema'] ?? '') === (string) $tema->id_tema)>{{ $tema->nombre }}</option>
            @endforeach
        </select>
        <label for="tipo">Tipo de reactivo</label>
        <select id="tipo" name="tipo" style="width:100%;padding:.5rem;">
            <option value="">Todos</option>
            @foreach (\App\Models\Pregunta::TIPOS as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(($filtros['tipo'] ?? '') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <label for="estado">Estado</label>
        <select id="estado" name="estado" style="width:100%;padding:.5rem;">
            <option value="">Todos</option>
            <option value="1" @selected((string) ($filtros['estado'] ?? '') === '1')>Activo</option>
            <option value="0" @selected((string) ($filtros['estado'] ?? '') === '0')>Inactivo</option>
        </select>
        <button type="submit">Filtrar</button>
        <a href="{{ route('coordinador.banco.index') }}">Limpiar filtros</a>
    </form>

    <p class="nota">{{ $preguntas->total() }} pregunta(s) encontrada(s).</p>
    @forelse ($preguntas as $pregunta)
        <section style="border:1px solid #d9dfe7; padding:1rem; border-radius:8px; margin:1rem 0;">
            <p><strong>#{{ $pregunta->id_pregunta }} — {{ $pregunta->enunciado }}</strong></p>
            <p class="nota">{{ $pregunta->tema->categoria->nombre }} / {{ $pregunta->tema->nombre }}<br>
                {{ \App\Models\Pregunta::TIPOS[$pregunta->tipo] ?? $pregunta->tipo }} · {{ $pregunta->opciones_count }} opciones · {{ $pregunta->estado ? 'Activa' : 'Inactiva' }}</p>
            @if ($pregunta->preguntas_intento_count === 0)
                <p><a href="{{ route('coordinador.banco.preguntas.edit', $pregunta) }}">Editar pregunta y opciones</a></p>
            @else
                <p class="nota">Reactivo utilizado en un examen. Edición bloqueada.</p>
            @endif
            <form method="POST" action="{{ route('coordinador.banco.preguntas.estado', $pregunta) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="estado" value="{{ $pregunta->estado ? '0' : '1' }}">
                <button type="submit">{{ $pregunta->estado ? 'Desactivar reactivo' : 'Activar reactivo' }}</button>
            </form>
        </section>
    @empty
        <p>No hay preguntas. Configura las categorías y temas antes de registrar los reactivos.</p>
    @endforelse
    @if ($preguntas->hasPages())
        <p>@if ($preguntas->previousPageUrl())<a href="{{ $preguntas->previousPageUrl() }}">Anterior</a>@endif
            Página {{ $preguntas->currentPage() }} de {{ $preguntas->lastPage() }}
            @if ($preguntas->nextPageUrl())<a href="{{ $preguntas->nextPageUrl() }}">Siguiente</a>@endif</p>
    @endif
@endsection
