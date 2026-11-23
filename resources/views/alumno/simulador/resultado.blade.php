@extends('layouts.auth')
@section('title', 'Resultado de la práctica')
@section('content')
<div class="coco-page-head"><div><span class="coco-eyebrow">PRÁCTICA FINALIZADA</span><h1>Resultado de la práctica</h1><p>¡Has concluido tu práctica! Este resultado es formativo y temporal.</p></div></div>
<section class="coco-panel" style="max-width:740px;margin:0 auto;text-align:center;padding:40px 30px">
    <span class="coco-eyebrow">DESEMPEÑO DE LA SESIÓN</span>
    <div style="font-size:clamp(43px,6vw,72px);font-weight:850;letter-spacing:-.06em;color:#0a9089;line-height:1.2">{{number_format($resultado['porcentaje'],2)}} %</div>
    <p style="font-size:17px;margin:15px 0">Respuestas correctas: <strong>{{ $resultado['aciertos'] }} de {{ $resultado['total'] }}</strong></p>
    <div style="height:13px;background:#e8f1f5;border-radius:99px;overflow:hidden"><div style="width:{{ min(100,max(0,$resultado['porcentaje'])) }}%;height:100%;background:linear-gradient(90deg,#0b8f89,#62d3c8);border-radius:99px"></div></div>
    <p class="coco-section-sub" style="margin:18px 0 30px">La práctica no genera calificación oficial ni historial permanente.</p>
    <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap"><a class="coco-btn" href="{{route('alumno.simulador.index')}}">Volver a practicar →</a><a class="coco-btn" style="background:#e9f2f4;color:#164c56" href="{{route('alumno.panel')}}">Ir a mi espacio</a></div>
</section>
@endsection
