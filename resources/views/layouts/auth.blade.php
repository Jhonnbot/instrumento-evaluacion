<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c1f3f">
    <title>@yield('title', 'Instrumento de Evaluación') | COCOSOFT</title>
    <link rel="stylesheet" href="{{ asset('css/cocosoft.css') }}">
</head>
<body>
@php
    $esAcceso = request()->routeIs('login', 'register');
    $esPublica = request()->routeIs('constancias.verificar.*');
    $esAlumno = request()->routeIs('alumno.*');
    $nombre = auth()->user()?->nombre_completo ?? 'Usuario';
    $iniciales = collect(preg_split('/\s+/u', trim($nombre)))->filter()->take(2)->map(fn ($s) => mb_strtoupper(mb_substr($s, 0, 1)))->implode('');
@endphp
@if ($esAcceso)
    <div class="coco-auth-shell">
        <div class="coco-auth-layout">
            <aside class="coco-auth-intro">
                <div class="coco-auth-brand"><span class="coco-brand-mark">C</span><div><strong>COCOSOFT</strong><small>INSTRUMENTO DE EVALUACIÓN</small></div></div>
                <div class="coco-auth-story">
                    <span class="coco-eyebrow">{{ request()->routeIs('register') ? 'COMIENZA TU ACCESO' : 'TU ESPACIO DE EVALUACIÓN' }}</span>
                    <h1>{{ request()->routeIs('register') ? 'Tu camino al logro' : 'Tu siguiente paso' }} <span>{{ request()->routeIs('register') ? 'empieza aquí.' : 'comienza aquí.' }}</span></h1>
                    <p>{{ request()->routeIs('register') ? 'Crea tu cuenta de alumno y prepárate para practicar, presentar evaluaciones y consultar tus resultados.' : 'Practica, presenta tu examen y consulta tus resultados desde una sola plataforma.' }}</p>
                    <div class="coco-auth-journey">
                        <strong>Tu ruta de evaluación</strong>
                        <div class="coco-auth-journey-steps"><div class="coco-auth-step"><i>▧</i><b>01 · Práctica</b>Prepárate a tu ritmo.</div><div class="coco-auth-step"><i>◉</i><b>02 · Evaluación</b>Demuestra tus conocimientos.</div><div class="coco-auth-step"><i>♧</i><b>03 · Constancia</b>Reconoce tu avance.</div></div>
                    </div>
                </div>
                <div class="coco-auth-footer">◈ &nbsp; Acceso seguro y personalizado · COCOSOFT</div>
            </aside>
            <main class="coco-auth-right">
                <div class="coco-auth-top"><span>BIENVENIDO DE NUEVO</span></div>
                <div class="coco-auth-form">
                    <span class="coco-eyebrow">{{ request()->routeIs('register') ? 'INSCRIPCIÓN A LA PLATAFORMA' : 'BIENVENIDO DE NUEVO' }}</span>
                    @if(session('mensaje'))<div class="exito" role="status">{{ session('mensaje') }}</div>@endif
                    @if($errors->any())
                        <div class="errores" role="alert" style="padding:14px;border-radius:10px;background:#fff0f2;color:#a63143;margin:16px 0">
                            <strong>No fue posible continuar:</strong>
                            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @yield('content')
                    <div class="coco-auth-small">© {{ date('Y') }} COCOSOFT · Instrumento de Evaluación</div>
                </div>
            </main>
        </div>
    </div>
@elseif ($esPublica)
    <header class="coco-public-bar"><div class="coco-public-bar-inner">
        <a class="coco-brand" href="{{ route('constancias.verificar.formulario') }}"><span class="coco-brand-mark">C</span><span><span class="coco-brand-name">COCOSOFT</span><span class="coco-brand-sub" style="display:block">INSTRUMENTO DE EVALUACIÓN</span></span></a>
        <nav aria-label="Navegación pública"><a href="{{ route('constancias.verificar.formulario') }}">Verificar constancia</a> &nbsp; · &nbsp; <a href="{{ route('login') }}">Iniciar sesión</a></nav>
    </div></header>
    <div class="coco-public-hero"><div class="coco-public-hero-inner"><span class="coco-eyebrow" style="color:#8eebe0">VERIFICACIÓN OFICIAL</span><h1>Verificación de constancias</h1><p>Consulta la autenticidad y vigencia de una constancia emitida por el Instrumento de Evaluación COCOSOFT.</p></div></div>
    <main class="coco-public-container"><div class="coco-content">
        @if(session('mensaje'))<p class="exito" role="status">{{session('mensaje')}}</p>@endif
        @if($errors->any())<div class="errores" role="alert"><ul>@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
        @yield('content')
    </div></main>
@else
    <div class="coco-sidebar-backdrop" data-coco-backdrop></div>
    <div class="coco-app">
        <aside class="coco-sidebar" data-coco-sidebar aria-label="{{ $esAlumno ? 'Navegación del alumno' : 'Navegación administrativa' }}">
            <a class="coco-brand" href="{{ $esAlumno ? route('alumno.panel') : route('coordinador.panel') }}"><span class="coco-brand-mark">C</span><span><span class="coco-brand-name">COCOSOFT</span><span class="coco-brand-sub" style="display:block">INSTRUMENTO DE EVALUACIÓN</span></span></a>
            <div class="coco-nav-group"><div class="coco-nav-label">{{ $esAlumno ? 'MI ESPACIO' : 'ADMINISTRACIÓN' }}</div><nav class="coco-nav" aria-label="Secciones">
                @if($esAlumno)
                    <a href="{{route('alumno.panel')}}" @class(['is-active'=>request()->routeIs('alumno.panel')]) @if(request()->routeIs('alumno.panel')) aria-current="page" @endif><span class="coco-nav-icon">▦</span>Inicio</a>
                    <a href="{{route('alumno.simulador.index')}}" @class(['is-active'=>request()->routeIs('alumno.simulador.*')])><span class="coco-nav-icon">◈</span>Simulador de práctica</a>
                    <a href="{{route('alumno.examen.index')}}" @class(['is-active'=>request()->routeIs('alumno.examen.*')])><span class="coco-nav-icon">◎</span>Examen formal</a>
                    <a href="{{route('alumno.historial.index')}}" @class(['is-active'=>request()->routeIs('alumno.historial.*')])><span class="coco-nav-icon">▤</span>Resultados e historial</a>
                    <a href="{{route('alumno.constancias.index')}}" @class(['is-active'=>request()->routeIs('alumno.constancias.*')])><span class="coco-nav-icon">♧</span>Mis constancias</a>
                    <a href="{{route('alumno.solicitudes.index')}}" @class(['is-active'=>request()->routeIs('alumno.solicitudes.*')])><span class="coco-nav-icon">✉</span>Solicitudes</a>
                @else
                    <a href="{{route('coordinador.panel')}}" @class(['is-active'=>request()->routeIs('coordinador.panel')])><span class="coco-nav-icon">▦</span>Panel principal</a>
                    <a href="{{route('coordinador.alumnos.index')}}" @class(['is-active'=>request()->routeIs('coordinador.alumnos.*')])><span class="coco-nav-icon">♙</span>Alumnos</a>
                    <a href="{{route('coordinador.banco.index')}}" @class(['is-active'=>request()->routeIs('coordinador.banco.index','coordinador.banco.preguntas.*','coordinador.banco.categorias.*','coordinador.banco.temas.*')])><span class="coco-nav-icon">▤</span>Banco de preguntas</a>
                    <a href="{{route('coordinador.banco.importacion.index')}}" @class(['is-active'=>request()->routeIs('coordinador.banco.importacion.*')])><span class="coco-nav-icon">⇧</span>Importar preguntas</a>
                    <a href="{{route('coordinador.historial.index')}}" @class(['is-active'=>request()->routeIs('coordinador.historial.*')])><span class="coco-nav-icon">◷</span>Historial general</a>
                    <a href="{{route('coordinador.solicitudes.index')}}" @class(['is-active'=>request()->routeIs('coordinador.solicitudes.*')])><span class="coco-nav-icon">✉</span>Solicitudes</a>
                    <a href="{{route('coordinador.constancias.index')}}" @class(['is-active'=>request()->routeIs('coordinador.constancias.*')])><span class="coco-nav-icon">♧</span>Constancias</a>
                @endif
            </nav></div>
            <div class="coco-side-bottom"><div class="coco-side-person"><span class="coco-avatar">{{ $iniciales ?: 'C' }}</span><span><span class="coco-side-name">{{ $nombre }}</span><br><span class="coco-side-role">{{ $esAlumno ? 'Alumno' : 'Coordinador' }}</span></span></div>
                <form method="POST" action="{{route('logout')}}">@csrf<button type="submit" class="coco-logout">⇥ &nbsp; Cerrar sesión</button></form>
            </div>
        </aside>
        <div class="coco-main">
            <header class="coco-topbar">
                <div style="display:flex;align-items:center;gap:14px"><button class="coco-mobile-menu" type="button" data-coco-menu aria-label="Abrir menú" aria-expanded="false">☰</button><div class="coco-crumb">{{ $esAlumno ? 'Mi espacio' : 'Administración' }} <span class="coco-crumb-sep">/</span> <strong>@yield('title','Panel principal')</strong></div></div>
                <div class="coco-user-chip"><span>{{ $esAlumno ? 'Mi cuenta' : 'Coordinación COCOSOFT' }}</span><span class="coco-avatar">{{ $iniciales ?: 'C' }}</span></div>
            </header>
            <main class="coco-container" id="contenido-principal">
                @if(session('mensaje'))<p class="exito" role="status">{{session('mensaje')}}</p>@endif
                @if($errors->any())<div class="errores" role="alert"><ul>@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
                <div class="coco-content">@yield('content')</div>
            </main>
        </div>
    </div>
@endif
<script src="{{asset('js/cocosoft.js')}}" defer></script>
</body>
</html>
