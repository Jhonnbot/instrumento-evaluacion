@extends('layouts.auth')
@section('title', 'Panel principal')
@section('content')
<div class="coco-hero">
    <span class="coco-eyebrow">TU ESPACIO DE APRENDIZAJE</span>
    <h1>¡Hola, {{ explode(' ', trim(auth()->user()->nombre_completo))[0] }}!</h1>
    <p>Continúa con tu preparación. Aquí encontrarás todo lo que necesitas para practicar, presentar tu evaluación y consultar tus logros.</p>
    <div class="coco-hero-actions"><a href="{{route('alumno.simulador.index')}}" class="coco-btn">Comenzar práctica &nbsp; →</a><a href="{{route('alumno.historial.index')}}" class="coco-btn outline">Ver mi historial</a></div>
</div>
<h2 class="coco-section-title">Tu evaluación de un vistazo</h2>
<p class="coco-section-sub">Consulta el estado de tu preparación y tus resultados.</p>
<div class="coco-metrics">
    <div class="coco-metric"><div class="coco-metric-top">Simulador de práctica <span class="coco-metric-icon">◈</span></div><strong>∞</strong><small>Prácticas ilimitadas durante el periodo habilitado</small></div>
    <div class="coco-metric"><div class="coco-metric-top">Evaluación formal <span class="coco-metric-icon">◎</span></div><strong>{{ $intentosUsados }}</strong><small>Intento(s) presentados o iniciados</small></div>
    <div class="coco-metric"><div class="coco-metric-top">Resultado oficial <span class="coco-metric-icon">♧</span></div><strong style="font-size:22px;margin-top:25px">{{ $aprobado ? 'Aprobado' : ($resultadosRecientes->isNotEmpty() ? 'En progreso' : 'Pendiente') }}</strong><small>{{ $resultadosRecientes->isNotEmpty() ? 'Último puntaje: '.$resultadosRecientes->first()->calificacion_final.'/1000' : 'Todavía sin resultados oficiales' }}</small></div>
    <div class="coco-metric"><div class="coco-metric-top">Solicitudes <span class="coco-metric-icon">✉</span></div><strong>{{ $solicitudesPendientes }}</strong><small>Solicitud(es) en revisión</small></div>
</div>
<h2 class="coco-section-title">¿Qué te gustaría hacer hoy?</h2>
<p class="coco-section-sub">Elige cómo continuar.</p>
<div class="coco-quicklinks">
    <a class="coco-quicklink" href="{{route('alumno.simulador.index')}}"><span class="coco-metric-icon">◈</span><strong>Simulador de práctica</strong><span>Refuerza tus conocimientos con preguntas del banco y revisa tus resultados formativos.</span><em>Ir al simulador →</em></a>
    <a class="coco-quicklink" href="{{route('alumno.examen.index')}}"><span class="coco-metric-icon">◎</span><strong>Evaluación formal</strong><span>Consulta tus oportunidades oficiales y presenta tu evaluación de conocimientos.</span><em>Ir a mi evaluación →</em></a>
    <a class="coco-quicklink" href="{{route('alumno.historial.index')}}"><span class="coco-metric-icon">▤</span><strong>Mi historial</strong><span>Revisa tus intentos oficiales y el desempeño por categoría y tema.</span><em>Consultar resultados →</em></a>
    <a class="coco-quicklink" href="{{route('alumno.constancias.index')}}"><span class="coco-metric-icon">♧</span><strong>Mis constancias</strong><span>Emite, descarga y verifica tus constancias aprobatorias.</span><em>Ver mis constancias →</em></a>
</div>
<div class="coco-columns">
    <section class="coco-panel"><h2>Evaluaciones recientes</h2><p class="coco-section-sub">Tus resultados oficiales más recientes</p>
        @forelse($resultadosRecientes as $resultado)
            <div class="coco-row"><div><strong>Evaluación de conocimientos · Intento #{{ $resultado->numero_intento }}</strong><small>{{ \App\Support\FechaMexico::mostrar($resultado->fecha_calificacion) }} (CDMX)</small></div><div style="text-align:right"><strong>{{ $resultado->calificacion_final }} / 1000</strong><span @class(['coco-pill', 'ok'=>$resultado->dictamen === 'APROBADO','fail'=>$resultado->dictamen !== 'APROBADO'])>{{ $resultado->dictamen }}</span></div></div>
        @empty <p class="coco-section-sub">Todavía no has presentado evaluaciones oficiales.</p>
        @endforelse
    </section>
    <aside class="coco-panel"><h2>Accesos rápidos</h2><p class="coco-section-sub">Continúa desde cualquier sección.</p>
        <div class="coco-row"><strong>Solicitudes de oportunidades</strong><a href="{{route('alumno.solicitudes.index')}}">Consultar →</a></div>
        <div class="coco-row"><strong>Examen formal</strong><a href="{{route('alumno.examen.index')}}">Acceder →</a></div>
        <div class="coco-row"><strong>Constancias digitales</strong><a href="{{route('alumno.constancias.index')}}">Ver →</a></div>
        <p class="coco-section-sub" style="margin-top:18px">ID de usuario: <strong>{{auth()->id()}}</strong></p>
    </aside>
</div>
@endsection
