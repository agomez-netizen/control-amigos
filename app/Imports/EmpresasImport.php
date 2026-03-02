<?php

namespace App\Imports;

use App\Models\Empresa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmpresasImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private bool $actualizar = true // updateOrCreate si true, si no solo crea
    ) {}

    public function collection(Collection $rows)
    {
        // Validación por filas (con mensajes decentes)
        $validator = Validator::make($rows->toArray(), [
            '*.nombre' => ['required','string','max:120'],
            '*.id_base_datos' => ['required','integer','exists:base_de_datos,id_base_datos'],
            '*.id_tipo_empresa' => ['nullable','integer','exists:tipo_empresa,id_tipo_empresa'],
            '*.activo' => ['nullable','in:0,1'],
            '*.proyectos' => ['nullable','in:0,1'],

            '*.descripcion' => ['nullable','string','max:255'],
            '*.pais' => ['nullable','string','max:80'],
            '*.departamento' => ['nullable','string','max:80'],
            '*.municipio' => ['nullable','string','max:80'],
            '*.gestor_aapos' => ['nullable','string','max:120'],
            '*.sitio_web' => ['nullable','string','max:255'],
            '*.notas' => ['nullable','string'],
            '*.detalles' => ['nullable','string'],
        ], [
            '*.nombre.required' => 'La columna nombre es obligatoria.',
            '*.id_base_datos.exists' => 'id_base_datos no existe en base_de_datos.',
        ]);

        $validator->validate();

        foreach ($rows as $row) {
            $data = [
                'nombre' => trim((string)($row['nombre'] ?? '')),
                'id_base_datos' => (int)$row['id_base_datos'],
                'id_tipo_empresa' => $row['id_tipo_empresa'] !== null ? (int)$row['id_tipo_empresa'] : null,
                'activo' => isset($row['activo']) ? (int)$row['activo'] : 1,
                'descripcion' => $row['descripcion'] ?? null,
                'pais' => $row['pais'] ?? null,
                'departamento' => $row['departamento'] ?? null,
                'municipio' => $row['municipio'] ?? null,
                'gestor_aapos' => $row['gestor_aapos'] ?? null,
                'proyectos' => isset($row['proyectos']) ? (int)$row['proyectos'] : 0,
                'sitio_web' => $row['sitio_web'] ?? null,
                'notas' => $row['notas'] ?? null,
                'detalles' => $row['detalles'] ?? null,
            ];

            // “Clave” para evitar duplicados: nombre + id_base_datos
            $where = [
                'nombre' => $data['nombre'],
                'id_base_datos' => $data['id_base_datos'],
            ];

            if ($this->actualizar) {
                Empresa::updateOrCreate($where, $data);
            } else {
                // Solo crear, ignorar si ya existe
                Empresa::firstOrCreate($where, $data);
            }
        }
    }
}
