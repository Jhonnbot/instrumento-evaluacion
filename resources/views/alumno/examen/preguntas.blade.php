@extends('layouts.auth')
@section('title','Examen formal en curso')
@section('content')
<h2>Examen formal — intento #{{ $intento->numero_intento }}</h2>
<p class="nota">Página {{ $pagina }} de {{ $paginas }} · dos preguntas por pantalla · sin pausa</p>
<p><strong>Tiempo restante: <span id="reloj" aria-live="polite">Calculando…</span></strong></p>
<p class="nota" id="estado-guardado">Las respuestas se guardan automáticamente al cambiarlas y al navegar.</p>
<form method="POST" id="form-examen" action="{{route('alumno.examen.guardar',[$intento->getKey(), $pagina])}}">
    @csrf
    @foreach($preguntas as $p)
        @php($id = (string)$p['id'])
        @php($seleccion = $p['respuesta'])
        <fieldset style="border:1px solid #b8c0ca;border-radius:8px;padding:1rem;margin-top:1rem">
            <legend><strong>Pregunta {{ $p['orden'] }} · {{ $p['categoria'] }}</strong></legend>
            <p>{{ $p['enunciado'] }}</p>
            @if($p['tipo']==='ordenamiento')
                <p class="nota">Indica la posición de cada elemento (1 = primero).</p>
                @foreach($p['opciones'] as $o)
                    <label for="orden-{{ $id }}-{{ $o['id'] }}">{{ $o['texto'] }}</label>
                    <select id="orden-{{ $id }}-{{ $o['id'] }}" name="respuestas[{{ $id }}][{{ $o['id'] }}]" style="width:100%;padding:.5rem;border:1px solid #b8c0ca;border-radius:6px">
                        <option value="">Sin responder</option>
                        @for($n=1;$n<=count($p['opciones']);$n++)
                            <option value="{{ $n }}" @selected((int)($seleccion[$o['id']] ?? 0)===$n)>{{ $n }}</option>
                        @endfor
                    </select>
                @endforeach
            @elseif($p['tipo']==='seleccion_multiple')
                <p class="nota">Selecciona todas las opciones correctas.</p>
                @foreach($p['opciones'] as $o)
                    <label style="font-weight:normal"><input style="width:auto" type="checkbox" name="respuestas[{{ $id }}][]" value="{{ $o['id'] }}" @checked(in_array($o['id'], (array)$seleccion, true))> {{ $o['texto'] }}</label>
                @endforeach
            @else
                <p class="nota">Selecciona una opción.</p>
                @foreach($p['opciones'] as $o)
                    <label style="font-weight:normal"><input style="width:auto" type="radio" name="respuestas[{{ $id }}]" value="{{ $o['id'] }}" @checked((int)$seleccion===$o['id'])> {{ $o['texto'] }}</label>
                @endforeach
            @endif
        </fieldset>
    @endforeach
    @if($pagina>1)<button type="submit" name="accion" value="anterior">Anterior</button>@endif
    @if($pagina<$paginas)<button type="submit" name="accion" value="siguiente">Siguiente</button>@endif
    <button type="submit" name="accion" value="finalizar" onclick="return confirm('¿Entregar definitivamente el examen? Ya no podrás modificar respuestas.')">Entregar examen</button>
</form>
<form id="form-tiempo" method="POST" action="{{route('alumno.examen.finalizar',$intento->getKey())}}">@csrf</form>
<script>
(() => {
    const caducidad = new Date(@json($expira)).getTime();
    const reloj = document.getElementById('reloj');
    const form = document.getElementById('form-examen');
    const estado = document.getElementById('estado-guardado');
    let tarea = null, guardando = Promise.resolve();
    let cerrando = false;
    const autoguardar = @json(route('alumno.examen.autoguardar', [$intento->getKey(), $pagina]));
    function guardar() {
        const datos = new FormData(form);
        // Enviar solo el token y las respuestas, nunca la acción de navegación.
        guardando = guardando.then(() => fetch(autoguardar, {
            method: 'POST', body: datos, headers: {'Accept': 'application/json'}, credentials: 'same-origin'
        })).then(async respuesta => {
            if (!respuesta.ok) throw new Error('No se guardaron las respuestas.');
            const data = await respuesta.json();
            if (data.concluido) { cerrando = true; window.location.href = @json(route('alumno.examen.resultado',$intento->getKey())); }
            else estado.textContent = 'Respuestas guardadas.';
        }).catch(() => { estado.textContent = 'No se pudo guardar automáticamente. Usa los botones de navegación.'; });
    }
    form.addEventListener('change', () => {
        estado.textContent = 'Guardando…';
        clearTimeout(tarea);
        tarea = setTimeout(() => { tarea = null; guardar(); }, 350);
    });
    function actualizar() {
        const segundos = Math.max(0, Math.ceil((caducidad - Date.now()) / 1000));
        reloj.textContent = String(Math.floor(segundos/60)).padStart(2,'0') + ':' + String(segundos%60).padStart(2,'0');
        if (segundos === 0 && !cerrando) {
            cerrando = true;
            form.querySelectorAll('button,input,select').forEach(el => el.disabled = true);
            document.getElementById('form-tiempo').submit();
        }
    }
    actualizar();
    setInterval(actualizar, 1000);
})();
</script>
@endsection
