@extends('layouts.auth')
@section('title', 'Temas del banco')
@section('content')
    <h2>Temas del banco de preguntas</h2>
    <p><a href="{{ route('coordinador.banco.index') }}">Volver al banco</a> · <a href="{{ route('coordinador.banco.categorias.index') }}">Categorías</a></p>
    <p class="nota">Cada tema pertenece a una categoría. No se puede cambiar de categoría un tema que ya contiene reactivos.</p>

    <form method="POST" action="{{ route('coordinador.banco.temas.store') }}" style="border:1px solid #d9dfe7; padding:1rem; border-radius:8px;">
        @csrf
        <h3>Agregar tema</h3>
        <label for="categoria_nueva">Categoría activa</label>
        <select id="categoria_nueva" name="id_categoria" required style="width:100%;padding:.65rem;margin-top:.25rem;">
            <option value="">Seleccionar categoría</option>
            @foreach ($categorias->where('estado', true) as $cat)
                <option value="{{ $cat->id_categoria }}" @selected(old('id_categoria') == $cat->id_categoria)>{{ $cat->nombre }}</option>
            @endforeach
        </select>
        <label for="tema_nuevo">Nombre</label>
        <input id="tema_nuevo" name="nombre" maxlength="150" required value="{{ old('nombre') }}">
        <label for="descripcion_nueva">Descripción (opcional)</label>
        <textarea id="descripcion_nueva" name="descripcion" rows="2" maxlength="5000" style="width:100%;box-sizing:border-box;">{{ old('descripcion') }}</textarea>
        <button type="submit">Crear tema</button>
    </form>

    <form method="GET" action="{{ route('coordinador.banco.temas.index') }}" style="margin:1.2rem 0;">
        <label for="categoria_filtro">Filtrar por categoría</label>
        <select id="categoria_filtro" name="categoria" style="width:100%;padding:.6rem;">
            <option value="">Todas</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat->id_categoria }}" @selected((string) $categoriaFiltro === (string) $cat->id_categoria)>{{ $cat->nombre }}</option>
            @endforeach
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <p class="nota">{{ $temas->total() }} tema(s).</p>
    @forelse ($temas as $tema)
        <section style="border:1px solid #d9dfe7; padding:1rem; border-radius:8px; margin:1rem 0;">
            <p><strong>#{{ $tema->id_tema }} — {{ $tema->nombre }}</strong></p>
            <p class="nota">Categoría: {{ $tema->categoria->nombre }} · {{ $tema->preguntas_count }} preguntas</p>
            <form method="POST" action="{{ route('coordinador.banco.temas.update', $tema) }}">
                @csrf @method('PUT')
                <label for="cat_tema_{{ $tema->id_tema }}">Categoría</label>
                <select id="cat_tema_{{ $tema->id_tema }}" name="id_categoria" required style="width:100%;padding:.6rem;" @disabled($tema->preguntas_count > 0)>
                    @foreach ($categorias as $cat)
                        <option value="{{ $cat->id_categoria }}" @selected($tema->id_categoria == $cat->id_categoria)>{{ $cat->nombre }}</option>
                    @endforeach
                </select>
                @if ($tema->preguntas_count > 0)
                    <input type="hidden" name="id_categoria" value="{{ $tema->id_categoria }}">
                @endif
                <label for="nombre_tema_{{ $tema->id_tema }}">Nombre</label>
                <input id="nombre_tema_{{ $tema->id_tema }}" name="nombre" maxlength="150" required value="{{ $tema->nombre }}">
                <label for="descripcion_tema_{{ $tema->id_tema }}">Descripción</label>
                <textarea id="descripcion_tema_{{ $tema->id_tema }}" name="descripcion" rows="2" maxlength="5000" style="width:100%;box-sizing:border-box;">{{ $tema->descripcion }}</textarea>
                <button type="submit">Guardar cambios</button>
            </form>
        </section>
    @empty
        <p>No hay temas para la categoría seleccionada.</p>
    @endforelse

    @if ($temas->hasPages())
        <p>@if ($temas->previousPageUrl())<a href="{{ $temas->previousPageUrl() }}">Anterior</a>@endif
            Página {{ $temas->currentPage() }} de {{ $temas->lastPage() }}
            @if ($temas->nextPageUrl())<a href="{{ $temas->nextPageUrl() }}">Siguiente</a>@endif</p>
    @endif
@endsection
