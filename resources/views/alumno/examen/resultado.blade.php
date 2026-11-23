@extends('layouts.auth')
@section('title', 'Resultado del examen formal')
@section('content')
<div class="coco-page-head"><div><span class="coco-eyebrow">MI EVALUACIÓN</span><h1>Resultado de evaluación</h1><p>Consulta tu calificación oficial y el desempeño por categoría.</p></div></div>
<div class="coco-columns" style="margin-top:0;grid-template-columns:1fr 1fr">
    <section class="coco-panel" style="text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center">
        <div style="border:12px solid {{ $resultado->dictamen === 'APROBADO' ? '#10b8ad' : '#e7a286' }};border-radius:50%;height:175px;width:175px;display:flex;flex-direction:column;align-items:center;justify-content:center;margin:7px auto 20px">
            <strong style="font-size:43px;letter-spacing:-.06em;line-height:1">{{ $resultado->calificacion_final }}</strong><span style="font-size:12px;color:#6a7c8f">de 1000</span>
        </div>
        <span @class(['coco-pill','ok'=>$resultado->dictamen==='APROBADO','fail'=>$resultado->dictamen!=='APROBADO'])>{{ $resultado->dictamen }}</span>
        <p class="coco-section-sub" style="margin-top:14px">{{ $resultado->dictamen === 'APROBADO' ? '¡Felicidades por tu resultado!' : 'Continúa fortaleciendo tus conocimientos.' }}</p>
    </section>
    <section class="coco-panel"><h2>Información de la evaluación</h2>
        <div class="coco-row"><span>Evaluación</span><strong>Evaluación de conocimientos</strong></div>
        <div class="coco-row"><span>Intento</span><strong>#{{ $actual->numero_intento }}</strong></div>
        <div class="coco-row"><span>Entrega</span><strong>{{ $actual->estado === 'tiempo_agotado' ? 'Tiempo agotado' : 'Enviado' }}</strong></div>
        <div class="coco-row"><span>Calificación mínima</span><strong>700 / 1000</strong></div>
        <div class="coco-row"><span>Puntaje obtenido</span><strong>{{ $resultado->calificacion_final }} / 1000</strong></div>
    </section>
</div>
<section class="coco-panel" style="margin-top:22px"><h2 style="margin-top:0">Desempeño por categoría</h2><p class="coco-section-sub">Cinco categorías, ponderadas al 20 % cada una.</p>
@foreach($detalle as $fila)
    @php($pct = max(0, min(100, (float)$fila->puntaje_obtenido / 2)))
    <div style="margin:17px 0"><div style="display:flex;justify-content:space-between;gap:10px;font-size:13px"><strong>{{ $fila->nombre }}</strong><span>{{ number_format((float)$fila->puntaje_obtenido, 2) }} / 200</span></div><div style="height:10px;border-radius:20px;background:#ecf3f6;overflow:hidden;margin-top:8px"><div style="width:{{ $pct }}%;background:linear-gradient(90deg,#0a8d88,#45c6b9);height:100%;border-radius:20px"></div></div></div>
@endforeach
</section>
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:20px"><a class="coco-btn" href="{{route('alumno.historial.index')}}">Mi historial →</a><a class="coco-btn" href="{{route('alumno.constancias.index')}}">Mis constancias</a><a href="{{route('alumno.examen.index')}}" style="align-self:center">Volver al examen</a></div>
@endsection
