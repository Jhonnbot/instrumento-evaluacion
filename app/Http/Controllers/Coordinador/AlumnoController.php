<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlumnoController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('buscar', ''));

        $alumnos = User::query()
            ->whereHas('rol', fn (Builder $query) => $query->where('nombre', 'Alumno'))
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $consulta) use ($busqueda): void {
                    $consulta
                        ->where('nombre_completo', 'like', '%'.$busqueda.'%')
                        ->orWhere('correo', 'like', '%'.$busqueda.'%')
                        ->orWhere('curp', 'like', '%'.$busqueda.'%');

                    if (ctype_digit($busqueda)) {
                        $consulta->orWhere('id_usuario', $busqueda);
                    }
                });
            })
            ->orderByDesc('id_usuario')
            ->paginate(10)
            ->withQueryString();

        return view('coordinador.alumnos.index', compact('alumnos', 'busqueda'));
    }

    public function edit(int $id): View
    {
        return view('coordinador.alumnos.edit', [
            'alumno' => $this->buscarAlumno($id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $alumno = $this->buscarAlumno($id);

        $request->merge([
            'nombre_completo' => trim((string) $request->input('nombre_completo')),
            'telefono' => trim((string) $request->input('telefono')),
            'correo' => mb_strtolower(trim((string) $request->input('correo'))),
            'curp' => mb_strtoupper(trim((string) $request->input('curp'))),
        ]);

        $datos = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => [
                'required', 'email', 'max:150',
                Rule::unique('usuarios', 'correo')->ignore($alumno->getKey(), 'id_usuario'),
            ],
            'curp' => [
                'required', 'size:18',
                'regex:/^[A-Z][AEIOUX][A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/',
                Rule::unique('usuarios', 'curp')->ignore($alumno->getKey(), 'id_usuario'),
            ],
        ]);

        // No permite cambiar id_rol, password_hash ni el estado por este formulario.
        $alumno->update($datos);

        return redirect()->route('coordinador.alumnos.index')
            ->with('mensaje', 'Datos del alumno actualizados correctamente.');
    }

    public function cambiarEstado(Request $request, int $id): RedirectResponse
    {
        $alumno = $this->buscarAlumno($id);

        $datos = $request->validate([
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);

        $alumno->update(['estado' => $datos['estado']]);

        return redirect()->route('coordinador.alumnos.index')
            ->with('mensaje', $datos['estado'] === 'activo'
                ? 'Cuenta del alumno activada.'
                : 'Cuenta del alumno desactivada.');
    }

    private function buscarAlumno(int $id): User
    {
        // El coordinador solo administra usuarios Alumno: nunca otros coordinadores.
        return User::query()
            ->whereHas('rol', fn (Builder $query) => $query->where('nombre', 'Alumno'))
            ->findOrFail($id);
    }
}
