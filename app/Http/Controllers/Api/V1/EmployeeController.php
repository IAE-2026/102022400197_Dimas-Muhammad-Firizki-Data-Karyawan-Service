<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return $this->success(Employee::query()->latest('id')->get(), 'Employees retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $employee = Employee::create($validated);

        return $this->success($employee, 'Employee created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $employee = Employee::query()
            ->where('id', $id)
            ->orWhere('employee_id', $id)
            ->first();

        if (! $employee) {
            return $this->error('Data karyawan tidak ditemukan.', 404);
        }

        return $this->success($employee, 'Employee retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $employee = Employee::query()
            ->where('id', $id)
            ->orWhere('employee_id', $id)
            ->first();

        if (! $employee) {
            return $this->error('Data karyawan tidak ditemukan.', 404);
        }

        $validated = $request->validate($this->rules($employee->id));
        $employee->update($validated);

        return $this->success($employee->fresh(), 'Employee updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::query()
            ->where('id', $id)
            ->orWhere('employee_id', $id)
            ->first();

        if (! $employee) {
            return $this->error('Data karyawan tidak ditemukan.', 404);
        }

        $employee->delete();

        return $this->success(null, 'Employee deleted successfully');
    }

    private function rules(?int $employeeId = null): array
    {
        return [
            'employee_id' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_id')->ignore($employeeId)],
            'nik' => ['required', 'string', 'max:30', Rule::unique('employees', 'nik')->ignore($employeeId)],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employeeId)],
            'position' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'fixed_allowance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'resigned'])],
        ];
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => [
                'service_name' => config('iae.service_name'),
                'api_version' => config('iae.api_version'),
            ],
        ], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => null,
        ], $status);
    }
}
