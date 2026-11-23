@extends('layouts.auth')
@section('title', 'Iniciar sesión')
@section('content')
<h2>Iniciar sesión</h2>
<p class="coco-auth-description">Ingresa tus datos para acceder al Instrumento de Evaluación.</p>
<form method="POST" action="{{url('/iniciar-sesion')}}">
    @csrf
    <label for="identificador">Correo electrónico o identificador</label>
    <input id="identificador" name="identificador" value="{{old('identificador')}}" placeholder="Ingresa tu correo o identificador" required autofocus autocomplete="username">
    <label for="password">Contraseña</label>
    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Ingresa tu contraseña">
    <button type="submit">Ingresar a mi cuenta &nbsp; →</button>
</form>
<div class="coco-auth-links">¿Aún no tienes cuenta? <a href="{{route('register')}}">Regístrate</a></div>
@endsection
