<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ArrayExport implements FromArray
{
    public function __construct(private array $data) {}

    public function array(): array
    {
        return $this->data;
    }
}
