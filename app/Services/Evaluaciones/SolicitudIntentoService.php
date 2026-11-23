<?php

namespace App\Services\Evaluaciones;

use App\Models\SolicitudIntento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Control de oportunidades extras. Las aprobaciones no consumidas no se duplican. */
class SolicitudIntentoService
{
    public function __construct(private ExamenService $examenes) {}

    /** Estado que comparte la interfaz y la validación del servidor. */
    public function estadoAlumno(User $alumno): array
    {
        $examen = $this->examenes->examen();
        $estadoExamen = $examen ? $this->examenes->estado($alumno, $examen) : null;
        $pendiente = SolicitudIntento::query()->where('id_usuario', $alumno->getKey())
            ->where('estado', 'pendiente')->exists();
        $puedeSolicitar = $estadoExamen !== null
            && $estadoExamen['requiere_autorizacion']
            && !$pendiente
            && $this->todosLosIntentosReprobados($estadoExamen['intentos']);

        return compact('examen', 'estadoExamen', 'pendiente', 'puedeSolicitar');
    }

    /** Se exige dictamen REPROBADO en todos los intentos finalizados. */
    private function todosLosIntentosReprobados($intentos): bool
    {
        if ($intentos->count() < 2 || $intentos->contains(fn ($i) => $i->estado === 'en_curso')) {
            return false;
        }
        return DB::table('resultados')->whereIn('id_intento', $intentos->pluck('id_intento'))
            ->where('dictamen', 'REPROBADO')->count() === $intentos->count();
    }

    /** Bloquea la fila del usuario, al igual que ExamenService::iniciar(). */
    public function solicitar(User $alumno): SolicitudIntento
    {
        return DB::transaction(function () use ($alumno): SolicitudIntento {
            DB::table('usuarios')->where('id_usuario', $alumno->getKey())->lockForUpdate()->first();
            if (!$alumno->fresh()?->estaActivo()) {
                throw ValidationException::withMessages(['solicitud' => 'La cuenta debe estar activa.']);
            }
            $estado = $this->estadoAlumno($alumno);
            if (!$estado['puedeSolicitar']) {
                throw ValidationException::withMessages(['solicitud' =>
                    $estado['pendiente'] ? 'Ya tienes una solicitud pendiente de revisión.' :
                    'Solo puedes solicitar otra oportunidad después de agotar los intentos disponibles sin aprobar.']);
            }
            return SolicitudIntento::create([
                'id_usuario' => $alumno->getKey(),
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
            ]);
        }, 3);
    }

    /** Rechaza o autoriza: una autorización habilita un intento y solo uno. */
    public function resolver(User $coordinador, int $idSolicitud, string $decision, ?string $observaciones): SolicitudIntento
    {
        if (!in_array($decision, ['aprobar', 'rechazar'], true)) {
            throw ValidationException::withMessages(['decision' => 'Decisión inválida.']);
        }

        return DB::transaction(function () use ($coordinador, $idSolicitud, $decision, $observaciones): SolicitudIntento {
            // Fijamos el orden de bloqueos: usuario y después solicitud.
            $idAlumno = SolicitudIntento::query()->findOrFail($idSolicitud)->id_usuario;
            DB::table('usuarios')->where('id_usuario', $idAlumno)->lockForUpdate()->first();
            $solicitud = SolicitudIntento::query()->lockForUpdate()->findOrFail($idSolicitud);
            if ($solicitud->estado !== 'pendiente') {
                throw ValidationException::withMessages(['solicitud' => 'Esta solicitud ya fue resuelta.']);
            }

            if ($decision === 'aprobar') {
                $alumno = User::query()->findOrFail($idAlumno);
                if (!$alumno->estaActivo()) {
                    throw ValidationException::withMessages(['solicitud' => 'No se puede autorizar a un alumno inactivo.']);
                }
                $estado = $this->estadoAlumno($alumno);
                // El estado marca pendiente (esta solicitud). Solo comprobamos los requisitos del examen.
                $e = $estado['estadoExamen'];
                if (!$e || !$e['requiere_autorizacion'] || !$this->todosLosIntentosReprobados($e['intentos'])) {
                    throw ValidationException::withMessages(['solicitud' => 'El alumno ya no cumple las condiciones para otro intento.']);
                }
                $autorizacionLibre = SolicitudIntento::query()->where('id_usuario', $idAlumno)
                    ->where('estado', 'aprobada')->whereDoesntHave('intento')->exists();
                if ($autorizacionLibre) {
                    throw ValidationException::withMessages(['solicitud' => 'El alumno ya tiene una oportunidad adicional sin utilizar.']);
                }
            }

            $solicitud->update([
                'estado' => $decision === 'aprobar' ? 'aprobada' : 'rechazada',
                'id_coordinador' => $coordinador->getKey(),
                'fecha_resolucion' => now(),
                'observaciones' => $observaciones,
            ]);
            return $solicitud->refresh();
        }, 3);
    }
}
