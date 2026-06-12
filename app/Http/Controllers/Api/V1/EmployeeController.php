<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\RabbitMqPublisher;
use App\Services\SoapAuditService;
use App\Services\SsoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly SsoService $sso,
        private readonly SoapAuditService $soapAudit,
        private readonly RabbitMqPublisher $publisher,
    ) {}

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

        try {
            [$employee, $receipt] = DB::transaction(function () use ($request, $validated): array {
                $employee = Employee::create($validated);
                $receipt = $this->integrateCriticalTransaction($request, 'EmployeeCreated', 'employee.created', $employee->toArray());

                return [$employee, $receipt];
            });
        } catch (Throwable $exception) {
            return $this->integrationError($exception);
        }

        return $this->success([
            'employee' => $employee,
            'integration' => ['audit_receipt_number' => $receipt, 'event' => 'employee.created'],
        ], 'Employee created and integrated successfully', 201);
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

        try {
            [$updated, $receipt] = DB::transaction(function () use ($request, $employee, $validated): array {
                $employee->update($validated);
                $updated = $employee->fresh();
                $receipt = $this->integrateCriticalTransaction($request, 'EmployeeUpdated', 'employee.updated', $updated->toArray());

                return [$updated, $receipt];
            });
        } catch (Throwable $exception) {
            return $this->integrationError($exception);
        }

        return $this->success([
            'employee' => $updated,
            'integration' => ['audit_receipt_number' => $receipt, 'event' => 'employee.updated'],
        ], 'Employee updated and integrated successfully');
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

        try {
            $receipt = DB::transaction(function () use ($request, $employee): string {
                $snapshot = $employee->toArray();
                $employee->delete();

                return $this->integrateCriticalTransaction($request, 'EmployeeDeleted', 'employee.deleted', $snapshot);
            });
        } catch (Throwable $exception) {
            return $this->integrationError($exception);
        }

        return $this->success([
            'employee_id' => $employee->employee_id,
            'integration' => ['audit_receipt_number' => $receipt, 'event' => 'employee.deleted'],
        ], 'Employee deleted and integrated successfully');
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

    private function integrateCriticalTransaction(
        Request $request,
        string $activityName,
        string $eventName,
        array $employee
    ): string {
        $actor = $request->attributes->get('federated_user');
        $payload = [
            'service' => config('iae.service_name'),
            'event' => $eventName,
            'occurred_at' => now()->toIso8601String(),
            'actor' => [
                'subject' => $actor->sso_subject,
                'email' => $actor->email,
                'local_role' => $actor->role->name,
            ],
            'employee' => $employee,
        ];

        $machineToken = $this->sso->machineToken();
        $receipt = $this->soapAudit->submit($machineToken, $activityName, $payload);

        AuditLog::create([
            'employee_id' => $employee['employee_id'],
            'activity_name' => $activityName,
            'event_name' => $eventName,
            'sso_subject' => $actor->sso_subject,
            'receipt_number' => $receipt,
            'status' => 'completed',
            'payload' => $payload,
        ]);

        $this->publisher->publish($machineToken, $eventName, [
            ...$payload,
            'audit_receipt_number' => $receipt,
        ]);

        return $receipt;
    }

    private function integrationError(Throwable $exception): JsonResponse
    {
        report($exception);

        $message = $exception instanceof RequestException
            ? 'Layanan pusat menolak atau gagal memproses transaksi.'
            : $exception->getMessage();

        return $this->error('Transaksi dibatalkan: '.$message, 502);
    }
}
