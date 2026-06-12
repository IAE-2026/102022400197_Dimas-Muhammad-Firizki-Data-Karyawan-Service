<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('iae.key')->group(function (): void {
    Route::post('v1/auth/login', [AuthController::class, 'login']);

    Route::get('v1/employees', [EmployeeController::class, 'index']);
    Route::get('v1/employees/{id}', [EmployeeController::class, 'show']);

    Route::middleware(['sso', 'role:hr_admin'])->group(function (): void {
        Route::post('v1/employees', [EmployeeController::class, 'store']);
        Route::match(['put', 'patch'], 'v1/employees/{id}', [EmployeeController::class, 'update']);
        Route::delete('v1/employees/{id}', [EmployeeController::class, 'destroy']);
    });
});
