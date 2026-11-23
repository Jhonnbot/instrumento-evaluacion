@extends('layouts.auth')
@section('title','Verificación pública de constancias')
@section('content')
<h2>Consultar constancia</h2>
<p class="nota">Introduce el folio y la clave impresos en el documento o accede desde el código QR. No es necesario iniciar sesión.</p>
<form method="POST" action="{{route('constancias.verificar.consultar')}}">
    @csrf
    <label for="folio">Folio de la constancia</label>
    <input id="folio" name="folio" required maxlength="64" value="{{old('folio')}}" autocomplete="off" placeholder="COCO-00000001">
    <label for="clave">Clave de verificación</label>
    <input id="clave" name="clave" required maxlength="128" value="{{old('clave')}}" autocomplete="off" placeholder="Introduce la clave impresa en el PDF">
    <button type="submit">Verificar constancia &nbsp; →</button>
</form>
@if($consultado)
    @if(!$constancia)
        <section style="margin-top:24px;border-left:4px solid #bb3547!important"><h3>Constancia no encontrada</h3><p>No existe un registro que coincida con el folio y la clave de verificación consultados.</p></section>
    @else
        <section style="margin-top:24px;border-left:4px solid {{ $constancia->estado==='vigente' ? '#0e9b85' : '#b33442' }}!important">
            @if($constancia->estado==='vigente')
                <h3 style="color:#0a7c5a">CONSTANCIA AUTÉNTICA Y VIGENTE</h3>
            @else
                <h3 style="color:#b33442">CONSTANCIA REVOCADA — NO VIGENTE</h3>
            @endif
            <div class="coco-row"><span>Folio</span><strong>{{ $constancia->folio }}</strong></div>
            <div class="coco-row"><span>Alumno</span><strong>{{ $constancia->resultado->intento->alumno?->nombre_completo }}</strong></div>
            <div class="coco-row"><span>Evaluación</span><strong>{{ $constancia->resultado->intento->examen?->nombre }}</strong></div>
            <div class="coco-row"><span>Fecha de evaluación</span><strong>{{ \App\Support\FechaMexico::mostrar($constancia->resultado->intento->fecha_fin) }} (CDMX)</strong></div>
            <div class="coco-row"><span>Calificación</span><strong>{{ $constancia->resultado->calificacion_final }} / 1000</strong></div>
            <div class="coco-row"><span>Dictamen</span><strong>{{ $constancia->resultado->dictamen }}</strong></div>
            <div class="coco-row"><span>Fecha de emisión</span><strong>{{ \App\Support\FechaMexico::mostrar($constancia->fecha_emision) }} (CDMX)</strong></div>
            @if($constancia->estado==='revocada')<p style="color:#b33442;margin-top:17px">Este folio existe, pero fue revocado. No debe aceptarse como una constancia vigente.</p>@endif
        </section>
    @endif
@endif
<div class="coco-panel" style="margin-top:22px;background:#f7fbfd"><h3 style="margin:0 0 12px">¿Cómo verificar tu constancia?</h3><p>1. Localiza el folio y la clave en la parte inferior del PDF.</p><p>2. Escríbelos en el formulario o escanea el QR.</p><p>3. Confirma que los datos coincidan y que el estado sea <strong>VIGENTE</strong>.</p></div>
@endsection
