@extends('layouts.auth')
@section('title', 'Importación masiva de preguntas')
@section('content')
    <h2>Importación masiva de preguntas — HU-04</h2>
    <p><a href="{{ route('coordinador.banco.index') }}">Volver al banco de preguntas</a> ·
        <a href="{{ route('coordinador.banco.categorias.index') }}">Categorías</a> ·
        <a href="{{ route('coordinador.banco.temas.index') }}">Temas</a></p>

    <p class="nota">Se aceptan archivos CSV UTF-8 de hasta 2 MB. Puedes editarlos en Excel y guardarlos como <strong>CSV UTF-8</strong>.
       La primera fila debe tener las cabeceras de la plantilla, separadas por comas o puntos y coma.</p>
    <p><a href="{{ route('coordinador.banco.importacion.plantilla') }}">Descargar plantilla CSV vacía</a></p>

    <section style="background:#f7f9fb;padding:1rem;border-radius:8px;">
        <p><strong>Cómo usar la plantilla</strong></p>
        <ol>
            <li>Primero crea las categorías y los temas en el banco.</li>
            <li>Asigna un <code>codigo_pregunta</code> único a cada reactivo dentro del archivo (por ejemplo, P001).</li>
            <li>Escribe <strong>una fila por opción</strong>, repitiendo código, categoría, tema, tipo y enunciado de esa pregunta.</li>
            <li><code>tipo</code>: <code>opcion_multiple</code>, <code>seleccion_multiple</code>, <code>verdadero_falso</code> u <code>ordenamiento</code>.</li>
            <li><code>es_correcta</code>: <code>1</code> o <code>0</code>. En ordenamiento utiliza siempre <code>0</code> y completa <code>orden_correcto</code> con 1, 2, 3… sin repeticiones. En los demás tipos deja <code>orden_correcto</code> vacío.</li>
        </ol>
        <p class="nota">Las categorías y los temas deben existir previamente y los nombres deben coincidir. Se admiten entre 2 y 20 opciones por pregunta. Cada reactivo inválido se rechaza completo sin afectar a los demás.</p>
    </section>

    <form action="{{ route('coordinador.banco.importacion.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="archivo">Archivo CSV UTF-8</label>
        <input id="archivo" type="file" name="archivo" accept=".csv,.txt,text/csv" required>
        <button type="submit">Validar e importar preguntas</button>
    </form>

    @if (session('reporte_importacion'))
        @php($reporte = session('reporte_importacion'))
        <section aria-label="Reporte de importación" style="margin-top:1.5rem;padding:1rem;border:1px solid #d9dfe7;border-radius:8px;">
            <h3>Resultado de la importación</h3>
            <p><strong>Preguntas detectadas:</strong> {{ $reporte['preguntas_detectadas'] }}</p>
            <p><strong>Importadas correctamente:</strong> {{ $reporte['importadas'] }}</p>
            <p><strong>Rechazadas:</strong> {{ $reporte['rechazadas'] }}</p>
            @if ($reporte['rechazadas'] > 0)
                <h4>Motivos de rechazo</h4>
                <ul>
                    @foreach ($reporte['errores'] as $error)
                        <li><strong>{{ $error['codigo'] }}:</strong> {{ $error['detalle'] }}</li>
                    @endforeach
                </ul>
                @if ($reporte['rechazadas'] > count($reporte['errores']))
                    <p class="nota">Se muestran los primeros {{ count($reporte['errores']) }} rechazos.</p>
                @endif
            @endif
            <p><a href="{{ route('coordinador.banco.index') }}">Ver preguntas importadas</a></p>
        </section>
    @endif
@endsection
