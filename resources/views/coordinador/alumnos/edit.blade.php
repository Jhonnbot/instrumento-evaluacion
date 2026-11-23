@extends('layouts.auth')
@section('title', 'Editar alumno')
@section('content')
    <h2>Editar alumno #{{ $alumno->id_usuario }}</h2>
    <p><a href="{{ route('coordinador.alumnos.index') }}">Volver al directorio</a></p>

    <form method="POST" action="{{ route('coordinador.alumnos.update', $alumno->id_usuario) }}">
        @csrf
        @method('PUT')

        <label for="nombre_completo">Nombre completo</label>
        <input id="nombre_completo" name="nombre_completo" required maxlength="150"
               value="{{ old('nombre_completo', $alumno->nombre_completo) }}">

        <label for="telefono">Teléfono</label>
        <input id="telefono" name="telefono" required maxlength="20"
               value="{{ old('telefono', $alumno->telefono) }}">

        <label for="correo">Correo electrónico</label>
        <input id="correo" type="email" name="correo" required maxlength="150"
               value="{{ old('correo', $alumno->correo) }}">

        <label for="curp">CURP</label>
        <input id="curp" name="curp" required maxlength="18" minlength="18"
               value="{{ old('curp', $alumno->curp) }}" style="text-transform:uppercase">

        <p class="nota">El identificador, la contraseña y el rol no se modifican desde este formulario.</p>
        <button type="submit">Guardar cambios</button>
    </form>
@endsection
