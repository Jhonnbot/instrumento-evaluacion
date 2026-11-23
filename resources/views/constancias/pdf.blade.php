<!doctype html>
<html lang="es"><head><meta charset="utf-8">
<style>
    @page {margin: 37px 46px;} body {font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1b2b41;}
    .top {border-bottom: 2px solid #007f83; padding-bottom: 12px;}
    .top-title {font-size: 18px; color: #12304b; font-weight: bold;}
    .subtitle {color:#52687f;font-size:9px;}
    h1 {text-align:center;font-size:17px;margin:18px 0;color:#12304b;}
    h2 {font-size:11px;color:#008387;margin:16px 0 6px;border-bottom:1px solid #c7dfe0;padding-bottom:3px;}
    table {width:100%;border-collapse:collapse;} th,td {padding:5px 7px;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:top;}
    th {background:#eff8f8;color:#154959;} .score {padding:10px;border:1px solid #a6cace;text-align:center;}
    .big {font-weight:bold;font-size:21px;color:#12304b;} .approved {color:#087e55;font-weight:bold;font-size:13px;}
    .footer {margin-top:15px;border-top:1px solid #93b3bf;padding-top:10px;}
    .qr {width:115px;height:115px;} .tiny {font-size:8px;color:#52687f;word-break:break-all;}
    tr {page-break-inside:avoid;}
</style></head><body>
<div class="top"><span class="top-title">COCOSOFT</span><span class="subtitle"> &nbsp; Instrumento de Evaluación y Certificación</span>
<div style="float:right;font-size:9px">Folio: {{ $constancia->folio }}</div></div>
<h1>CONSTANCIA DE EVALUACIÓN</h1>
<h2>DATOS DEL ALUMNO</h2>
<p><strong>Nombre:</strong> {{ $intento->alumno?->nombre_completo }}<br><strong>Identificador:</strong> {{ $intento->id_usuario }}</p>
<h2>DATOS DE LA EVALUACIÓN</h2>
<p><strong>Examen:</strong> {{ $intento->examen?->nombre ?? 'Evaluación de conocimientos' }}<br>
<strong>Intento:</strong> {{ $intento->numero_intento }}<br><strong>Fecha del examen:</strong> {{ $fechaEvaluacion }} (Ciudad de México)</p>
<div class="score"><span class="big">{{ $resultado->calificacion_final }} / 1000</span> &nbsp;&nbsp; <span class="approved">APROBADO</span> &nbsp;&nbsp; Mínimo: 700 / 1000</div>
<h2>RESULTADO POR CATEGORÍA (20 % CADA UNA)</h2>
<table><thead><tr><th>Categoría</th><th>Puntos</th><th>Porcentaje</th></tr></thead><tbody>
@foreach($categorias as $categoria)
<tr><td>{{ $categoria->nombre }}</td><td>{{ number_format((float)$categoria->puntaje_obtenido,2) }} / 200</td><td>{{ number_format((float)$categoria->puntaje_obtenido / 2,2) }} %</td></tr>
@endforeach
</tbody></table>
@if($temas->isNotEmpty())
<h2>DESGLOSE POR TEMA</h2>
<table><thead><tr><th>Categoría / tema</th><th>Aciertos</th></tr></thead><tbody>
@foreach($temas as $tema)
<tr><td>{{ $tema->categoria }} / {{ $tema->tema }}</td><td>{{ $tema->respuestas_correctas }} de {{ $tema->preguntas_totales }}</td></tr>
@endforeach
</tbody></table>
@endif
<div class="footer">
<table style="border:0"><tr><td style="width:72%">
<strong>Fecha de emisión:</strong> {{ $fechaEmision }} (CDMX)<br>
<strong>Folio:</strong> {{ $constancia->folio }}<br>
<strong>Clave de verificación:</strong> <span style="font-size:8px">{{ $constancia->clave_verificacion }}</span>
<p class="tiny">Para verificar la vigencia de este documento, escanea el código QR o consulta folio y clave en el portal oficial. Una constancia revocada dejará de aparecer como vigente.</p>
<p class="tiny">{{ $urlVerificacion }}</p>
</td><td style="width:28%;text-align:right"><img class="qr" src="{{ $qr }}" alt="Código QR de verificación"></td></tr></table>
</div></body></html>
