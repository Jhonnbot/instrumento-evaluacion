@extends('layouts.auth')
@section('title', 'Categorías del banco')
@section('content')
    <h2>Categorías del banco de preguntas</h2>
    <p><a href="{{ route('coordinador.banco.index') }}">Volver al banco de preguntas</a></p>
    <p class="nota">La evaluación se distribuye en <strong>5 categorías con 20 % de ponderación cada una</strong>. Los nombres oficiales se capturan aquí. No se pueden agregar más de cinco ni modificar su ponderación.</p>
    <p><strong>{{ $categorias->count() }} de 5 categorías configuradas.</strong></p>

    @if ($categorias->count() < 5)
        <form method="POST" action="{{ route('coordinador.banco.categorias.store') }}" style="border:1px solid #d9dfe7; padding:1rem; border-radius:8px; margin:1rem 0;">
            @csrf
            <h3>Agregar categoría</h3>
            <label for="nombre_nueva">Nombre oficial</label>
            <input id="nombre_nueva" name="nombre" maxlength="120" required value="{{ old('nombre') }}" placeholder="Nombre de la categoría">
            <button type="submit">Crear categoría (20 %)</button>
        </form>
    @endif

    @forelse ($categorias as $categoria)
        <section style="border:1px solid #d9dfe7; padding:1rem; border-radius:8px; margin:1rem 0;">
            <p><strong>Categoría #{{ $categoria->id_categoria }}</strong> — Ponderación: <strong>{{ number_format((float) $categoria->ponderacion * 100, 0) }} %</strong></p>
            <p class="nota">{{ $categoria->temas_count }} temas · {{ $categoria->estado ? 'Activa' : 'Inactiva' }}</p>
            <form method="POST" action="{{ route('coordinador.banco.categorias.update', $categoria) }}">
                @csrf @method('PUT')
                <label for="nombre_categoria_{{ $categoria->id_categoria }}">Nombre</label>
                <input id="nombre_categoria_{{ $categoria->id_categoria }}" name="nombre" maxlength="120" required value="{{ $categoria->nombre }}">
                <button type="submit">Guardar nombre</button>
            </form>
            <form method="POST" action="{{ route('coordinador.banco.categorias.estado', $categoria) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="estado" value="{{ $categoria->estado ? '0' : '1' }}">
                <button type="submit">{{ $categoria->estado ? 'Desactivar categoría' : 'Activar categoría' }}</button>
            </form>
        </section>
    @empty
        <p>No hay categorías. Registra los nombres oficiales para comenzar.</p>
    @endforelse
    <p><a href="{{ route('coordinador.banco.temas.index') }}">Continuar con los temas</a></p>
@endsection
