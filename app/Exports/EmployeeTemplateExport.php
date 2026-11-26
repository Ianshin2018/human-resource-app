<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeeTemplateExport implements FromArray, WithColumnFormatting, WithHeadings
{
    public function array(): array
    {
        return [
            ['EMP-001', '王小明', '人資部', '人資專員', 'example@example.com', '0912345678', '2026-11-26'],
        ];
    }

    public function headings(): array
    {
        return ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
