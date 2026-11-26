<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_number',
        'name',
        'department',
        'position',
        'email',
        'phone',
        'hire_date',
    ];
}
