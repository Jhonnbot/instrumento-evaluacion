@extends('layouts.auth')
@section('title','Preguntas de práctica')
@section('content')
<h2>Simulador de práctica</h2>
<p class="nota">Página {{ $pagina }} de {{ $paginas }} · {{ $total }} preguntas · dos por pantalla</p>
<p class="nota">Puedes modificar tus respuestas antes de terminar; las correctas no se revelan durante la práctica.</p>
<form method="POST" action="{{route('alumno.simulador.guardar',$pagina)}}">
    @csrf <input type="hidden" name="token" value="{{ $token }}">
    @foreach($preguntas as $i => $p)
    @php($id = (string)$p['id'])
    @php($seleccion = $respuestas[$id] ?? null)
    <fieldset style="border:1px solid #b8c0ca;border-radius:8px;padding:1rem;margin-top:1rem">
        <legend><strong>Pregunta {{ ($pagina-1)*2+$i+1 }} · {{ $p['categoria'] }}</strong></legend>
        <p>{{ $p['enunciado'] }}</p>
        @if($p['tipo']==='ordenamiento')
            <p class="nota">Indica la posición de cada paso (1 = primero).</p>
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
            <p class="nota">Marca todas las opciones que correspondan.</p>
            @foreach($p['opciones'] as $o)
                <label style="font-weight:normal"><input style="width:auto" type="checkbox" name="respuestas[{{ $id }}][]" value="{{ $o['id'] }}" @checked(in_array($o['id'],(array)$seleccion,true))> {{ $o['texto'] }}</label>
            @endforeach
        @else
            <p class="nota">Elige una opción.</p>
            @foreach($p['opciones'] as $o)
                <label style="font-weight:normal"><input style="width:auto" type="radio" name="respuestas[{{ $id }}]" value="{{ $o['id'] }}" @checked((int)$seleccion===$o['id'])> {{ $o['texto'] }}</label>
            @endforeach
        @endif
    </fieldset>
    @endforeach
    @if($pagina>1)<button type="submit" name="accion" value="anterior">Anterior</button>@endif
    @if($pagina<$paginas)<button type="submit" name="accion" value="siguiente">Siguiente</button>@endif
    <button type="submit" name="accion" value="finalizar" onclick="return confirm('¿Finalizar práctica y mostrar resultado?')">Finalizar práctica</button>
</form>
@endsection
