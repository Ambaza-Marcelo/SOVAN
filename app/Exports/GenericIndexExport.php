<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenericIndexExport implements FromCollection, WithHeadings
{
    protected $rows;
    protected $headings;

    public function __construct($rows, $headings = [])
    {
        $this->rows = is_array($rows) ? collect($rows) : $rows;
        $this->headings = $headings;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
