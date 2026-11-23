<?php

namespace App\Services\Constancias;

use App\Models\Constancia;
use App\Models\Resultado;
use App\Services\Evaluaciones\HistorialResultadosService;
use App\Support\FechaMexico;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Folios estables; documentos privados; clave aleatoria no derivada del ID. */
class ConstanciaService
{
    public function emitir(Resultado $resultado, int $idAlumno): Constancia
    {
        // Solo el dueño puede emitirla y solo si aprobó.
        $resultado->loadMissing('intento.alumno', 'intento.examen');
        abort_unless($resultado->intento && (int) $resultado->intento->id_usuario === $idAlumno, 404);
        if ($resultado->dictamen !== 'APROBADO' || (int) $resultado->calificacion_final < 700) {
            throw ValidationException::withMessages(['constancia' => 'Solo se emiten constancias de exámenes aprobados.']);
        }

        return DB::transaction(function () use ($resultado): Constancia {
            // Bloqueo por resultado para que dos solicitudes simultáneas no emitan dos folios.
            $bloqueado = Resultado::query()->whereKey($resultado->getKey())->lockForUpdate()->firstOrFail();
            $existente = Constancia::query()->where('id_resultado', $bloqueado->getKey())->first();
            if ($existente) {
                return $existente; // Nunca se reemite ni se vuelve vigente una revocada.
            }

            $folio = 'COCO-'.str_pad((string) $bloqueado->getKey(), 8, '0', STR_PAD_LEFT);
            $clave = Str::random(40);
            $ruta = 'constancias/'.$folio.'.pdf';
            $constancia = Constancia::query()->create([
                'id_resultado' => $bloqueado->getKey(),
                'folio' => $folio,
                'clave_verificacion' => $clave,
                'fecha_emision' => now(),
                'ruta_pdf' => $ruta,
                'estado' => 'vigente',
            ]);

            // El PDF queda inmutable en un disco PRIVADO, jamás en public/storage.
            $documento = $this->crearPdf($constancia);
            if (! Storage::disk('local')->put($ruta, $documento)) {
                throw new \RuntimeException('No se pudo almacenar la constancia PDF.');
            }
            return $constancia;
        });
    }

    public function pdfExistente(Constancia $constancia): string
    {
        if (! Storage::disk('local')->exists($constancia->ruta_pdf)) {
            // No reconstruimos un documento con datos que podrían haber cambiado.
            throw new \RuntimeException('El PDF original no está disponible. Revisa el almacenamiento privado.');
        }
        return Storage::disk('local')->get($constancia->ruta_pdf);
    }

    private function crearPdf(Constancia $constancia): string
    {
        $resultado = $constancia->resultado()->with(['intento.alumno', 'intento.examen'])->firstOrFail();
        $intento = $resultado->intento;
        $detalle = app(HistorialResultadosService::class)->detalle($intento);
        $url = route('constancias.verificar.qr', ['clave' => $constancia->clave_verificacion]);
        $renderer = new ImageRenderer(new RendererStyle(220, 5), new SvgImageBackEnd());
        $qrSvg = (new Writer($renderer))->writeString($url);
        $qr = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);

        return Pdf::loadView('constancias.pdf', [
            'constancia' => $constancia,
            'resultado' => $resultado,
            'intento' => $intento,
            'categorias' => $detalle['categorias'],
            'temas' => $detalle['temas'],
            'qr' => $qr,
            'urlVerificacion' => $url,
            'fechaEmision' => FechaMexico::mostrar($constancia->fecha_emision),
            'fechaEvaluacion' => FechaMexico::mostrar($intento->fecha_fin ?? $resultado->fecha_calificacion),
        ])->setPaper('letter', 'portrait')->output();
    }
}
