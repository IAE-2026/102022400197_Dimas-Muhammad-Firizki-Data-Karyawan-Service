<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'nik',
        'name',
        'email',
        'position',
        'department',
        'base_salary',
        'fixed_allowance',
        'status',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'fixed_allowance' => 'decimal:2',
    ];
}
