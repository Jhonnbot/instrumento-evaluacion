@extends('layouts.auth')
@section('title', 'Panel administrativo')
@section('content')
<div class="coco-page-head"><div><span class="coco-eyebrow">ESPACIO DE ADMINISTRACIÓN</span><h1>Panel administrativo</h1><p>Consulta la actividad general y accede a las herramientas de gestión.</p></div><span class="coco-date">{{ now('America/Mexico_City')->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</span></div>
<div class="coco-metrics">
    <div class="coco-metric"><div class="coco-metric-top">Alumnos registrados <span class="coco-metric-icon">♙</span></div><strong>{{ $alumnosRegistrados }}</strong><small>Alumnos en la plataforma</small></div>
    <div class="coco-metric"><div class="coco-metric-top">Evaluaciones presentadas <span class="coco-metric-icon">▤</span></div><strong>{{ $evaluacionesPresentadas }}</strong><small>Exámenes finalizados</small></div>
    <div class="coco-metric"><div class="coco-metric-top">Evaluaciones aprobadas <span class="coco-metric-icon">✓</span></div><strong>{{ $evaluacionesAprobadas }}</strong><small>Resultados aprobatorios</small></div>
    <div class="coco-metric"><div class="coco-metric-top">Solicitudes pendientes <span class="coco-metric-icon">◷</span></div><strong>{{ $solicitudesPendientes }}</strong><small>Por revisar</small></div>
</div>
<h2 class="coco-section-title">Accesos rápidos</h2>
<p class="coco-section-sub">Administra las funciones principales desde un solo lugar.</p>
<div class="coco-quicklinks">
    <a class="coco-quicklink" href="{{route('coordinador.alumnos.index')}}"><span class="coco-metric-icon">♙</span><strong>Gestión de alumnos</strong><span>Consulta y administra las cuentas registradas.</span><em>Ver alumnos →</em></a>
    <a class="coco-quicklink" href="{{route('coordinador.banco.index')}}"><span class="coco-metric-icon">▤</span><strong>Banco de preguntas</strong><span>Revisa los reactivos organizados por categoría y tema.</span><em>Consultar banco →</em></a>
    <a class="coco-quicklink" href="{{route('coordinador.banco.importacion.index')}}"><span class="coco-metric-icon">⇧</span><strong>Importar preguntas</strong><span>Incorpora nuevas preguntas mediante archivos CSV.</span><em>Ir a importación →</em></a>
    <a class="coco-quicklink" href="{{route('coordinador.solicitudes.index')}}"><span class="coco-metric-icon">✉</span><strong>Solicitudes</strong><span>Revisa y responde las oportunidades de evaluación.</span><em>Revisar solicitudes →</em></a>
</div>
<div class="coco-columns">
    <section class="coco-panel"><div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap"><div><h2>Evaluaciones recientes</h2><p class="coco-section-sub">Últimos resultados registrados</p></div><a href="{{route('coordinador.historial.index')}}">Ver historial →</a></div>
        @forelse($resultadosRecientes as $resultado)
            <div class="coco-row"><div><strong>{{ $resultado->nombre_completo }}</strong><small>{{ \App\Support\FechaMexico::mostrar($resultado->fecha_calificacion) }} · Intento #{{ $resultado->numero_intento }}</small></div><div style="text-align:right"><strong>{{ $resultado->calificacion_final }}/1000</strong><span @class(['coco-pill','ok'=>$resultado->dictamen === 'APROBADO','fail'=>$resultado->dictamen !== 'APROBADO'])>{{ $resultado->dictamen }}</span></div></div>
        @empty <p class="coco-section-sub">Sin evaluaciones registradas.</p> @endforelse
    </section>
    <aside class="coco-panel"><div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap"><div><h2>Solicitudes por revisar</h2><p class="coco-section-sub">Oportunidades pendientes</p></div><span class="coco-pill warn">{{ $solicitudesPendientes }} pendientes</span></div>
        @forelse($solicitudesRecientes as $solicitud)
            <div class="coco-row"><div><strong>{{ $solicitud->nombre_completo }}</strong><small>{{ \App\Support\FechaMexico::mostrar($solicitud->fecha_solicitud) }} (CDMX)</small></div><span class="coco-pill warn">En revisión</span></div>
        @empty <p class="coco-section-sub">No hay solicitudes pendientes.</p> @endforelse
        <p style="margin-top:22px"><a href="{{route('coordinador.solicitudes.index')}}">Administrar solicitudes →</a></p>
        <p><a href="{{route('coordinador.constancias.index')}}">Consultar constancias →</a></p>
    </aside>
</div>
@endsection
