<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('iae.key')->group(function (): void {
    Route::post('v1/auth/login', [AuthController::class, 'login']);

    foreach (['employees', 'employee', 'karyawan', 'karyawans'] as $resource) {
        Route::get("v1/{$resource}", [EmployeeController::class, 'index']);
        Route::get("v1/{$resource}/{id}", [EmployeeController::class, 'show']);
        Route::post("v1/{$resource}", [EmployeeController::class, 'store']);

        Route::middleware(['sso', 'role:hr_admin'])->group(function () use ($resource): void {
            Route::match(['put', 'patch'], "v1/{$resource}/{id}", [EmployeeController::class, 'update']);
            Route::delete("v1/{$resource}/{id}", [EmployeeController::class, 'destroy']);
        });
    }
});

Route::any('{fallbackPlaceholder}', function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Resource tidak ditemukan.',
        'errors' => null,
    ], 404);
})->where('fallbackPlaceholder', '.*')->middleware('iae.key');
