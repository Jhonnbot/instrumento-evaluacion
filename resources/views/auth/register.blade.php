@extends('layouts.auth')
@section('title', 'Registro de alumnos')
@section('content')
<h2>Crear mi cuenta</h2>
<p class="coco-auth-description">Ingresa tus datos personales. Todos los campos son obligatorios para completar el registro.</p>
<form method="POST" action="{{url('/registro')}}">
    @csrf
    <label for="nombre_completo">Nombre completo</label>
    <input id="nombre_completo" name="nombre_completo" value="{{old('nombre_completo')}}" required maxlength="150" autocomplete="name" placeholder="Ej. María Fernanda López García">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
        <div><label for="telefono">Teléfono</label><input id="telefono" name="telefono" value="{{old('telefono')}}" required maxlength="20" autocomplete="tel" placeholder="10 dígitos"></div>
        <div><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" value="{{old('correo')}}" required maxlength="150" autocomplete="email" placeholder="correo@ejemplo.com"></div>
    </div>
    <label for="curp">CURP</label>
    <input id="curp" name="curp" value="{{old('curp')}}" required minlength="18" maxlength="18" autocomplete="off" style="text-transform:uppercase" placeholder="18 caracteres">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
        <div><label for="password">Contraseña</label><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"></div>
        <div><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></div>
    </div>
    <p style="font-size:11px;color:#7c91a0">Mínimo 12 caracteres, con letras, números y símbolos.</p>
    <button type="submit">Crear cuenta de alumno &nbsp; →</button>
</form>
<div class="coco-auth-links">¿Ya tienes una cuenta? <a href="{{route('login')}}">Inicia sesión</a></div>
@endsection
