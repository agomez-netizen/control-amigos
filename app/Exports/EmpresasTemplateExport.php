<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmpresasTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'nombre',
            'id_base_datos',
            'id_tipo_empresa',
            'activo',
            'descripcion',
            'pais',
            'departamento',
            'municipio',
            'gestor_aapos',
            'proyectos',
            'sitio_web',
            'notas',
            'detalles',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Tecnologia Avanzada S.A.',
                3,      // id_base_datos
                2,      // id_tipo_empresa
                1,      // activo (1/0)
                'Proveedor de soluciones',
                'Guatemala',
                'Guatemala',
                'Guatemala',
                'Juan Pérez',
                0,      // proyectos (1/0)
                'https://ejemplo.com',
                'Notas opcionales',
                'Detalles opcionales',
            ],
            [
                'Comercial XYZ',
                3,
                null,   // id_tipo_empresa opcional
                1,
                null,
                'Guatemala',
                'Sacatepéquez',
                'Antigua',
                null,
                1,
                null,
                null,
                null,
            ],
        ];
    }
}
