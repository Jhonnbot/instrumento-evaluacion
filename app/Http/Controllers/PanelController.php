<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Datos reales para los paneles de los prototipos PI-03 y PI-10. */
class PanelController extends Controller
{
    public function alumno(): View
    {
        $usuarioId = Auth::id();

        $resultados = DB::table('resultados as r')
            ->join('intentos as i', 'i.id_intento', '=', 'r.id_intento')
            ->where('i.id_usuario', $usuarioId)
            ->select('r.calificacion_final', 'r.dictamen', 'r.fecha_calificacion', 'i.numero_intento', 'i.id_intento')
            ->orderByDesc('r.fecha_calificacion')->limit(5)->get();

        return view('panel.alumno', [
            'resultadosRecientes' => $resultados,
            'intentosUsados' => DB::table('intentos')->where('id_usuario', $usuarioId)->count(),
            'aprobado' => DB::table('resultados as r')->join('intentos as i', 'i.id_intento', '=', 'r.id_intento')
                ->where('i.id_usuario', $usuarioId)->where('r.dictamen', 'APROBADO')->exists(),
            'solicitudesPendientes' => DB::table('solicitudes_intento')->where('id_usuario', $usuarioId)->where('estado', 'pendiente')->count(),
        ]);
    }

    public function coordinador(): View
    {
        $recientes = DB::table('resultados as r')
            ->join('intentos as i', 'i.id_intento', '=', 'r.id_intento')
            ->join('usuarios as u', 'u.id_usuario', '=', 'i.id_usuario')
            ->select('u.nombre_completo', 'i.numero_intento', 'r.calificacion_final', 'r.dictamen', 'r.fecha_calificacion')
            ->orderByDesc('r.fecha_calificacion')->limit(5)->get();

        $solicitudes = DB::table('solicitudes_intento as s')
            ->join('usuarios as u', 'u.id_usuario', '=', 's.id_usuario')
            ->where('s.estado', 'pendiente')
            ->select('u.nombre_completo', 's.fecha_solicitud', 's.id_solicitud')
            ->orderByDesc('s.fecha_solicitud')->limit(4)->get();

        return view('panel.coordinador', [
            'alumnosRegistrados' => DB::table('usuarios as u')->join('roles as r', 'r.id_rol', '=', 'u.id_rol')->where('r.nombre', 'Alumno')->count(),
            'evaluacionesPresentadas' => DB::table('resultados')->count(),
            'evaluacionesAprobadas' => DB::table('resultados')->where('dictamen', 'APROBADO')->count(),
            'solicitudesPendientes' => DB::table('solicitudes_intento')->where('estado', 'pendiente')->count(),
            'resultadosRecientes' => $recientes,
            'solicitudesRecientes' => $solicitudes,
        ]);
    }
}
