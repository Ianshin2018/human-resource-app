<?php

namespace App\Imports;

class EmployeeImport
{
    public const HEADINGS = [
        'employee_number' => '員工編號',
        'name' => '姓名',
        'department' => '部門',
        'position' => '職稱',
        'email' => '電子郵件',
        'phone' => '電話',
        'hire_date' => '到職日期',
    ];
}
