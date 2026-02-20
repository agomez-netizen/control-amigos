<?php

namespace App\Exports;

use App\Models\Avance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AvancesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $desde;
    protected $hasta;
    protected $empresa;

    public function __construct($desde = null, $hasta = null, $proyecto = null)
    {
        $this->desde    = $desde;
        $this->hasta    = $hasta;
        $this->empresa = $empresa;
    }

    public function collection()
    {
        return Avance::with(['proyecto', 'user'])
            ->when($this->proyecto, fn($q) =>
                $q->where('id_proyecto', $this->proyecto)
            )
            ->when($this->desde, fn($q) =>
                $q->whereDate('fecha', '>=', $this->desde)
            )
            ->when($this->hasta, fn($q) =>
                $q->whereDate('fecha', '<=', $this->hasta)
            )
            ->orderBy('fecha', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Proyecto',
            'Descripción',
            'Usuario',
            'Hora'
        ];
    }

    public function map($avance): array
    {
        return [
            $avance->fecha,
            $avance->proyecto->nombre ?? '—',
            $avance->descripcion,
            $avance->user->nombre ?? 'Usuario eliminado',
            optional($avance->created_at)->format('H:i'),
        ];
    }
}
