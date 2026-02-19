<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmpresaUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => ['sometimes','required','string','max:120',"unique:empresa,nombre,$id,id_empresa"],
            'descripcion' => ['nullable','string','max:255'],

            'nombre_contacto' => ['nullable','string','max:100'],
            'apellido_contacto' => ['nullable','string','max:100'],
            'titulo' => ['nullable','string','max:100'],
            'puesto' => ['nullable','string','max:120'],

            'pais' => ['nullable','string','max:80'],
            'departamento' => ['nullable','string','max:120'],
            'municipio' => ['nullable','string','max:120'],
            'direccion' => ['nullable','string','max:255'],

            'email' => ['nullable','email','max:150'],
            'sitio_web' => ['nullable','string','max:180'],
            'notas' => ['nullable','string'],
            'detalles' => ['nullable','string'],

            'tipo_empresa' => ['nullable','string','max:80'],
            'gestor_aapos' => ['nullable','string','max:120'],
            'asistente' => ['nullable','string','max:120'],
            'nombre_base_datos' => ['nullable','string','max:120'],
            'correlativo_carta' => ['nullable','integer','min:1'],

            'activo' => ['nullable','integer','in:0,1'],

            // Si los mandas, se reemplazan todos (simple y claro)
            'telefonos' => ['nullable','array'],
            'telefonos.*.telefono' => ['required_with:telefonos','string','max:30'],
            'telefonos.*.nota' => ['nullable','string','max:120'],

            'celulares' => ['nullable','array'],
            'celulares.*.celular' => ['required_with:celulares','string','max:30'],
            'celulares.*.nota' => ['nullable','string','max:120'],
        ];
    }
}
