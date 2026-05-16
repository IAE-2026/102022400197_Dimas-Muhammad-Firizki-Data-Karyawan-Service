<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('iae.key')->group(function (): void {
    Route::apiResource('v1/employees', EmployeeController::class);
});
