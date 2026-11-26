<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?string $department = null,
    ) {}

    public function collection()
    {
        $query = Employee::query();
        $search = trim((string) $this->search);
        $department = trim((string) $this->department);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }
        if ($department !== '') {
            $query->where('department', $department);
        }

        return $query->orderBy('id')->get();
    }

    public function headings(): array
    {
        return ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->name,
            $employee->department,
            $employee->position,
            $employee->email,
            $employee->phone,
            $employee->hire_date,
        ];
    }
}
