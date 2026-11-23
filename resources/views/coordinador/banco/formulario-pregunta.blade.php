@extends('layouts.auth')
@section('title', $pregunta ? 'Editar pregunta' : 'Crear pregunta')
@section('content')
    <h2>{{ $pregunta ? 'Editar pregunta #'.$pregunta->id_pregunta : 'Crear pregunta' }}</h2>
    <p><a href="{{ route('coordinador.banco.index') }}">Volver al banco de preguntas</a></p>
    <p class="nota">Guarda al menos dos opciones. Una pregunta de opción múltiple lleva una respuesta correcta; la selección múltiple, al menos dos; Verdadero/Falso, una; y ordenamiento, todas las posiciones consecutivas.</p>

    @php
        $opcionesGuardadas = $pregunta?->opciones->map(fn ($o) => [
            'texto_opcion' => $o->texto_opcion,
            'es_correcta' => $o->es_correcta ? '1' : '0',
            'orden_correcto' => $o->orden_correcto,
        ])->all();
        $opcionesIniciales = old('opciones', $opcionesGuardadas ?: [
            ['texto_opcion' => '', 'es_correcta' => '0', 'orden_correcto' => null],
            ['texto_opcion' => '', 'es_correcta' => '0', 'orden_correcto' => null],
        ]);
        $opcionesIniciales = is_array($opcionesIniciales) ? array_values($opcionesIniciales) : [];
    @endphp

    <form method="POST" action="{{ $pregunta ? route('coordinador.banco.preguntas.update', $pregunta) : route('coordinador.banco.preguntas.store') }}" id="formulario-pregunta">
        @csrf
        @if ($pregunta) @method('PUT') @endif
        <label for="id_tema">Tema (solo categorías activas)</label>
        <select id="id_tema" name="id_tema" required style="width:100%;padding:.65rem; margin-top:.25rem;">
            <option value="">Selecciona un tema</option>
            @foreach ($temas as $tema)
                <option value="{{ $tema->id_tema }}" @selected((string) old('id_tema', $pregunta?->id_tema) === (string) $tema->id_tema)>
                    {{ $tema->categoria->nombre }} / {{ $tema->nombre }}
                </option>
            @endforeach
        </select>
        @if ($temas->isEmpty())
            <p class="errores">No hay temas de categorías activas. <a href="{{ route('coordinador.banco.categorias.index') }}">Configura categorías</a> y <a href="{{ route('coordinador.banco.temas.index') }}">temas</a> antes de continuar.</p>
        @endif

        <label for="enunciado">Enunciado</label>
        <textarea name="enunciado" id="enunciado" maxlength="10000" rows="4" required style="width:100%;box-sizing:border-box; padding:.65rem;">{{ old('enunciado', $pregunta?->enunciado) }}</textarea>
        <label for="tipo">Tipo de reactivo</label>
        <select id="tipo" name="tipo" required style="width:100%;padding:.65rem; margin-top:.25rem;">
            @foreach (\App\Models\Pregunta::TIPOS as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('tipo', $pregunta?->tipo ?? 'opcion_multiple') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <h3>Opciones de respuesta</h3>
        <p class="nota" id="ayuda-opciones">Marca las opciones correctas o indica el orden correcto según el tipo de reactivo.</p>
        <div id="lista-opciones">
            @foreach ($opcionesIniciales as $i => $opcion)
                <div class="fila-opcion" style="border:1px solid #d9dfe7;border-radius:8px;padding:.75rem;margin:.65rem 0;">
                    <label>Texto de opción
                        <input class="texto-opcion" name="opciones[{{ $i }}][texto_opcion]" maxlength="2000" required value="{{ $opcion['texto_opcion'] ?? '' }}">
                    </label>
                    <label class="campo-correcta" style="display:flex;align-items:center;gap:.5rem;">
                        <input type="checkbox" class="correcta-opcion" name="opciones[{{ $i }}][es_correcta]" value="1" @checked((bool) ($opcion['es_correcta'] ?? false)) style="width:auto;">
                        Esta opción es correcta
                    </label>
                    <label class="campo-orden">Posición correcta (1, 2, 3...)
                        <input type="number" min="1" class="orden-opcion" name="opciones[{{ $i }}][orden_correcto]" value="{{ $opcion['orden_correcto'] ?? '' }}">
                    </label>
                    <button type="button" class="quitar-opcion" style="background:#526172;margin-top:.6rem;">Quitar opción</button>
                </div>
            @endforeach
        </div>
        <button type="button" id="agregar-opcion" style="background:#526172;">+ Agregar opción</button>
        <p class="nota">La validación del servidor rechazará configuraciones inválidas. Una pregunta utilizada en un examen ya no podrá editarse.</p>
        <p><button type="submit" @disabled($temas->isEmpty())>{{ $pregunta ? 'Guardar cambios' : 'Crear pregunta' }}</button></p>
    </form>

    <script>
    (() => {
        const lista = document.getElementById('lista-opciones');
        const tipo = document.getElementById('tipo');
        const agregar = document.getElementById('agregar-opcion');
        const ayuda = document.getElementById('ayuda-opciones');
        const filas = () => [...lista.querySelectorAll('.fila-opcion')];

        function crearFila(texto = '') {
            const fila = document.createElement('div');
            fila.className = 'fila-opcion';
            fila.style.cssText = 'border:1px solid #d9dfe7;border-radius:8px;padding:.75rem;margin:.65rem 0;';
            fila.innerHTML = '<label>Texto de opción<input class="texto-opcion" maxlength="2000" required></label>'
                + '<label class="campo-correcta" style="display:flex;align-items:center;gap:.5rem;">'
                + '<input type="checkbox" class="correcta-opcion" value="1" style="width:auto;">Esta opción es correcta</label>'
                + '<label class="campo-orden">Posición correcta (1, 2, 3...)<input type="number" min="1" class="orden-opcion"></label>'
                + '<button type="button" class="quitar-opcion" style="background:#526172;margin-top:.6rem;">Quitar opción</button>';
            fila.querySelector('.texto-opcion').value = texto;
            lista.appendChild(fila);
            return fila;
        }

        function actualizarNumeros() {
            filas().forEach((fila, i) => {
                fila.querySelector('.texto-opcion').name = `opciones[${i}][texto_opcion]`;
                fila.querySelector('.correcta-opcion').name = `opciones[${i}][es_correcta]`;
                fila.querySelector('.orden-opcion').name = `opciones[${i}][orden_correcto]`;
            });
        }

        function ajustarTipo() {
            const valor = tipo.value;
            const vf = valor === 'verdadero_falso';
            const ordenamiento = valor === 'ordenamiento';
            if (vf) {
                const existentes = filas();
                const mismos = existentes.length === 2 &&
                    existentes[0].querySelector('.texto-opcion').value.toLowerCase() === 'verdadero' &&
                    existentes[1].querySelector('.texto-opcion').value.toLowerCase() === 'falso';
                if (!mismos) {
                    lista.replaceChildren();
                    crearFila('Verdadero');
                    crearFila('Falso');
                }
            }
            filas().forEach((fila, i) => {
                const texto = fila.querySelector('.texto-opcion');
                texto.readOnly = vf;
                if (vf) texto.value = i === 0 ? 'Verdadero' : 'Falso';
                fila.querySelector('.campo-correcta').style.display = ordenamiento ? 'none' : 'flex';
                fila.querySelector('.correcta-opcion').disabled = ordenamiento;
                fila.querySelector('.campo-orden').style.display = ordenamiento ? 'block' : 'none';
                fila.querySelector('.orden-opcion').disabled = !ordenamiento;
                fila.querySelector('.quitar-opcion').hidden = vf;
            });
            agregar.hidden = vf;
            ayuda.textContent = ordenamiento
                ? 'Escribe una posición correcta única para cada opción: 1, 2, 3... No marques casillas.'
                : vf ? 'Elige exactamente una opción correcta entre Verdadero y Falso.'
                : valor === 'seleccion_multiple' ? 'Marca al menos dos respuestas correctas.'
                : 'Marca exactamente una respuesta correcta.';
            actualizarNumeros();
        }

        agregar.addEventListener('click', () => {
            if (filas().length >= 20) return alert('Máximo 20 opciones.');
            crearFila(); ajustarTipo();
        });
        lista.addEventListener('click', (evento) => {
            if (!evento.target.classList.contains('quitar-opcion')) return;
            if (filas().length <= 2) return alert('Debes conservar al menos dos opciones.');
            evento.target.closest('.fila-opcion').remove(); ajustarTipo();
        });
        tipo.addEventListener('change', ajustarTipo);
        ajustarTipo();
    })();
    </script>
@endsection
