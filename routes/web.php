<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Coordinador\AlumnoController;
use App\Http\Controllers\Coordinador\CategoriaController;
use App\Http\Controllers\Coordinador\TemaController;
use App\Http\Controllers\Coordinador\PreguntaController;
use App\Http\Controllers\Coordinador\ImportacionPreguntasController;
use App\Http\Controllers\Alumno\SimuladorController;
use App\Http\Controllers\Alumno\ExamenController;
use App\Http\Controllers\Alumno\SolicitudIntentoController as SolicitudAlumnoController;
use App\Http\Controllers\Coordinador\SolicitudIntentoController as SolicitudCoordinadorController;
use App\Http\Controllers\Alumno\HistorialController as HistorialAlumnoController;
use App\Http\Controllers\Coordinador\HistorialController as HistorialCoordinadorController;
use App\Http\Controllers\Alumno\ConstanciaController as ConstanciaAlumnoController;
use App\Http\Controllers\Coordinador\ConstanciaController as ConstanciaCoordinadorController;
use App\Http\Controllers\Publico\VerificarConstanciaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanelController;

Route::view('/', 'welcome')->name('home');

// Consulta pública: QR con clave aleatoria y formulario de folio + clave.
Route::get('/verificar-constancia', [VerificarConstanciaController::class, 'index'])->name('constancias.verificar.formulario');
Route::post('/verificar-constancia', [VerificarConstanciaController::class, 'consultar'])->middleware('throttle:10,1')->name('constancias.verificar.consultar');
Route::get('/verificar-constancia/{clave}', [VerificarConstanciaController::class, 'porQr'])->middleware('throttle:30,1')->name('constancias.verificar.qr');

Route::middleware('guest')->group(function (): void {
    Route::get('/registro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/registro', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/iniciar-sesion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/cerrar-sesion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:Alumno')->prefix('alumno')->name('alumno.')->group(function (): void {
        Route::get('/', [PanelController::class, 'alumno'])->name('panel');
        Route::get('/constancias', [ConstanciaAlumnoController::class, 'index'])->name('constancias.index');
        Route::post('/constancias/{resultado}/generar', [ConstanciaAlumnoController::class, 'generar'])->whereNumber('resultado')->middleware('throttle:5,1')->name('constancias.generar');
        Route::get('/constancias/{constancia}/descargar', [ConstanciaAlumnoController::class, 'descargar'])->whereNumber('constancia')->name('constancias.descargar');
        Route::get('/historial', [HistorialAlumnoController::class, 'index'])->name('historial.index');
        Route::get('/historial/{intento}', [HistorialAlumnoController::class, 'detalle'])->whereNumber('intento')->name('historial.detalle');
        // HU-08: solicitud de intentos extra (únicamente alumno activo).
        Route::get('/solicitudes', [SolicitudAlumnoController::class, 'index'])->name('solicitudes.index');
        Route::post('/solicitudes', [SolicitudAlumnoController::class, 'store'])->name('solicitudes.store')->middleware('throttle:5,1');
        // HU-07: examen oficial persistente (50 minutos y 2 preguntas por pantalla).
        Route::get('/examen', [ExamenController::class, 'index'])->name('examen.index');
        Route::post('/examen/iniciar', [ExamenController::class, 'iniciar'])->name('examen.iniciar')->middleware('throttle:5,1');
        Route::get('/examen/{intento}/preguntas/{pagina}', [ExamenController::class, 'preguntas'])->whereNumber('intento')->whereNumber('pagina')->name('examen.preguntas');
        Route::post('/examen/{intento}/preguntas/{pagina}', [ExamenController::class, 'guardar'])->whereNumber('intento')->whereNumber('pagina')->name('examen.guardar');
        Route::post('/examen/{intento}/preguntas/{pagina}/autoguardar', [ExamenController::class, 'autoguardar'])->whereNumber('intento')->whereNumber('pagina')->name('examen.autoguardar');
        Route::post('/examen/{intento}/finalizar', [ExamenController::class, 'finalizar'])->whereNumber('intento')->name('examen.finalizar');
        Route::get('/examen/{intento}/resultado', [ExamenController::class, 'resultado'])->whereNumber('intento')->name('examen.resultado');
        Route::get('/simulador', [SimuladorController::class, 'index'])->name('simulador.index');
        Route::post('/simulador/iniciar', [SimuladorController::class, 'iniciar'])->name('simulador.iniciar')->middleware('throttle:10,1');
        Route::get('/simulador/preguntas/{pagina}', [SimuladorController::class, 'preguntas'])->whereNumber('pagina')->name('simulador.preguntas');
        Route::post('/simulador/preguntas/{pagina}', [SimuladorController::class, 'guardar'])->whereNumber('pagina')->name('simulador.guardar');
        Route::get('/simulador/resultado', [SimuladorController::class, 'resultado'])->name('simulador.resultado');
    });
    Route::get('/coordinador', [PanelController::class, 'coordinador'])->middleware('role:Coordinador')->name('coordinador.panel');

    Route::prefix('coordinador')->name('coordinador.')->middleware('role:Coordinador')->group(function (): void {
        Route::get('/constancias', [ConstanciaCoordinadorController::class, 'index'])->name('constancias.index');
        Route::post('/constancias/{constancia}/revocar', [ConstanciaCoordinadorController::class, 'revocar'])->whereNumber('constancia')->middleware('throttle:10,1')->name('constancias.revocar');
        Route::get('/historial', [HistorialCoordinadorController::class, 'index'])->name('historial.index');
        Route::get('/historial/{intento}', [HistorialCoordinadorController::class, 'detalle'])->whereNumber('intento')->name('historial.detalle');
        // HU-08: las decisiones son exclusivas del Coordinador.
        Route::get('/solicitudes', [SolicitudCoordinadorController::class, 'index'])->name('solicitudes.index');
        Route::post('/solicitudes/{solicitud}/resolver', [SolicitudCoordinadorController::class, 'resolver'])->whereNumber('solicitud')->name('solicitudes.resolver')->middleware('throttle:20,1');
        Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('/alumnos/{id}/editar', [AlumnoController::class, 'edit'])->whereNumber('id')->name('alumnos.edit');
        Route::put('/alumnos/{id}', [AlumnoController::class, 'update'])->whereNumber('id')->name('alumnos.update');
        Route::patch('/alumnos/{id}/estado', [AlumnoController::class, 'cambiarEstado'])->whereNumber('id')->name('alumnos.estado');

        // HU-04: importación masiva de CSV UTF-8; no se permite a alumnos ni invitados.
        Route::get('/banco/importacion', [ImportacionPreguntasController::class, 'index'])->name('banco.importacion.index');
        Route::get('/banco/importacion/plantilla', [ImportacionPreguntasController::class, 'plantilla'])->name('banco.importacion.plantilla');
        Route::post('/banco/importacion', [ImportacionPreguntasController::class, 'importar'])->name('banco.importacion.store')->middleware('throttle:5,1');

        // HU-05: banco de preguntas, categorias y temas; todas las rutas son del Coordinador.
        Route::get('/banco', [PreguntaController::class, 'index'])->name('banco.index');
        Route::get('/banco/categorias', [CategoriaController::class, 'index'])->name('banco.categorias.index');
        Route::post('/banco/categorias', [CategoriaController::class, 'store'])->name('banco.categorias.store');
        Route::put('/banco/categorias/{categoria}', [CategoriaController::class, 'update'])->name('banco.categorias.update');
        Route::patch('/banco/categorias/{categoria}/estado', [CategoriaController::class, 'estado'])->name('banco.categorias.estado');
        Route::get('/banco/temas', [TemaController::class, 'index'])->name('banco.temas.index');
        Route::post('/banco/temas', [TemaController::class, 'store'])->name('banco.temas.store');
        Route::put('/banco/temas/{tema}', [TemaController::class, 'update'])->name('banco.temas.update');
        Route::get('/banco/preguntas/nueva', [PreguntaController::class, 'create'])->name('banco.preguntas.create');
        Route::post('/banco/preguntas', [PreguntaController::class, 'store'])->name('banco.preguntas.store');
        Route::get('/banco/preguntas/{pregunta}/editar', [PreguntaController::class, 'edit'])->name('banco.preguntas.edit');
        Route::put('/banco/preguntas/{pregunta}', [PreguntaController::class, 'update'])->name('banco.preguntas.update');
        Route::patch('/banco/preguntas/{pregunta}/estado', [PreguntaController::class, 'estado'])->name('banco.preguntas.estado');
    });
});
