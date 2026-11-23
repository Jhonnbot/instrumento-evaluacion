<?php

namespace App\Http\Requests\Coordinador;

use App\Services\BancoPreguntas\ReglasPregunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GuardarPreguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estaActivo() && $this->user()?->tieneRol('Coordinador');
    }

    protected function prepareForValidation(): void
    {
        $opciones = $this->input('opciones');
        if (is_array($opciones)) {
            foreach ($opciones as &$opcion) {
                if (is_array($opcion) && isset($opcion['texto_opcion']) && is_string($opcion['texto_opcion'])) {
                    $opcion['texto_opcion'] = trim($opcion['texto_opcion']);
                }
            }
            unset($opcion);
        }

        $this->merge([
            'enunciado' => is_string($this->input('enunciado')) ? trim($this->input('enunciado')) : $this->input('enunciado'),
            'opciones' => $opciones,
        ]);
    }

    public function rules(): array
    {
        return ReglasPregunta::rules();
    }

    public function after(): array
    {
        return [fn (Validator $validator) => ReglasPregunta::comprobar($validator, $validator->getData())];
    }
}
