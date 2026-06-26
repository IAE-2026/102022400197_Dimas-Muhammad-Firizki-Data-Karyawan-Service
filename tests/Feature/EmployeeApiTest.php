<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FederatedUser;
use App\Models\Role;
use App\Services\RabbitMqPublisher;
use App\Services\SoapAuditService;
use App\Services\SsoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = '102022400197';

    public function test_can_create_and_list_employees(): void
    {
        $this->fakeSuccessfulIntegrations();

        $create = $this->criticalRequest()
            ->postJson('/api/v1/employees', $this->payload());

        $create->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.employee.employee_id', 'EMP-001')
            ->assertJsonPath('data.integration.audit_receipt_number', 'IAE-LOG-2026-TEST')
            ->assertJsonPath('meta.service_name', 'Data-Karyawan-Service');

        $this->assertDatabaseHas('audit_logs', [
            'employee_id' => 'EMP-001',
            'receipt_number' => 'IAE-LOG-2026-TEST',
            'event_name' => 'employee.created',
        ]);

        $list = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/employees');

        $list->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.name', 'Dimas Pratama');
    }

    public function test_can_create_and_list_karyawan(): void
    {
        $this->fakeSuccessfulIntegrations();

        $create = $this->criticalRequest()
            ->postJson('/api/v1/karyawan', $this->payload());

        $create->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.employee.employee_id', 'EMP-001')
            ->assertJsonPath('data.integration.audit_receipt_number', 'IAE-LOG-2026-TEST')
            ->assertJsonPath('meta.service_name', 'Data-Karyawan-Service');

        $list = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/karyawan');

        $list->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.name', 'Dimas Pratama');
    }

    public function test_karyawan_detail_and_actions(): void
    {
        $this->fakeSuccessfulIntegrations();

        Employee::create($this->payload());

        // Get Detail
        $detail = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/karyawan/EMP-001');
        $detail->assertOk()
            ->assertJsonPath('data.name', 'Dimas Pratama');

        // Update
        $updatedPayload = $this->payload();
        $updatedPayload['name'] = 'Dimas Updated';
        $update = $this->criticalRequest()
            ->putJson('/api/v1/karyawan/EMP-001', $updatedPayload);
        $update->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.employee.name', 'Dimas Updated');

        // Delete
        $delete = $this->criticalRequest()
            ->deleteJson('/api/v1/karyawan/EMP-001');
        $delete->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_global_exception_wrapper_for_route_not_found(): void
    {
        $response = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/non_existent_route_path');

        $response->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Resource tidak ditemukan.')
            ->assertJsonPath('errors', null);
    }

    public function test_can_get_employee_detail(): void
    {
        Employee::create($this->payload());

        $response = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/employees/EMP-001');

        $response->assertOk()
            ->assertJsonPath('data.email', 'dimas@example.com');
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/v1/employees');

        $response->assertUnauthorized()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('errors', null);
    }

    public function test_unknown_employee_returns_404(): void
    {
        $response = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/employees/UNKNOWN');

        $response->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    public function test_validation_error_returns_422(): void
    {
        $this->fakeSuccessfulIntegrations();

        $response = $this->criticalRequest()
            ->postJson('/api/v1/employees', []);

        $response->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonValidationErrors(['employee_id', 'nik', 'name']);
    }

    public function test_swagger_and_openapi_are_available(): void
    {
        $this->get('/docs')->assertOk();
        $this->get('/openapi.json')
            ->assertOk()
            ->assertJsonPath('info.title', 'Data Karyawan Service API');
    }

    public function test_graphql_query_returns_selected_fields(): void
    {
        Employee::create($this->payload());

        $response = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->postJson('/graphql', [
                'query' => 'query { employees { employee_id name status } }',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.employees.0.employee_id', 'EMP-001')
            ->assertJsonPath('data.employees.0.name', 'Dimas Pratama')
            ->assertJsonMissingPath('data.employees.0.nik');
    }

    public function test_graphql_playground_is_available(): void
    {
        $this->get('/graphiql')->assertOk();
    }

    public function test_graphql_requires_api_key(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => 'query { employees { name } }',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('status', 'error');
    }

    public function test_task_2_create_employee_only_requires_api_key(): void
    {
        $response = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->postJson('/api/v1/employees', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.employee.employee_id', 'EMP-001')
            ->assertJsonPath('data.integration', null)
            ->assertJsonPath('meta.service_name', 'Data-Karyawan-Service');

        $this->assertDatabaseHas('employees', [
            'employee_id' => 'EMP-001',
            'email' => 'dimas@example.com',
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_publisher_failure_rolls_back_employee_and_audit_log(): void
    {
        $this->fakeFederatedIdentity();
        $this->mock(SoapAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('submit')->once()->andReturn('IAE-LOG-2026-ROLLBACK');
        });
        $this->mock(RabbitMqPublisher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('publish')->once()->andThrow(new RuntimeException('Publisher unavailable'));
        });

        $response = $this->criticalRequest()
            ->postJson('/api/v1/employees', $this->payload());

        $response->assertStatus(502)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_local_role_is_enforced(): void
    {
        $this->fakeFederatedIdentity('viewer');

        $response = $this->criticalRequest()
            ->postJson('/api/v1/employees', $this->payload());

        $response->assertForbidden()
            ->assertJsonPath('message', 'Role lokal tidak diizinkan melakukan transaksi ini.');
    }

    private function payload(): array
    {
        return [
            'employee_id' => 'EMP-001',
            'nik' => '3276010101010001',
            'name' => 'Dimas Pratama',
            'email' => 'dimas@example.com',
            'position' => 'Staff HR',
            'department' => 'Human Resource',
            'base_salary' => 5500000,
            'fixed_allowance' => 750000,
            'status' => 'active',
        ];
    }

    private function criticalRequest(): static
    {
        return $this->withHeaders([
            'X-IAE-KEY' => self::API_KEY,
            'Authorization' => 'Bearer test-user-jwt',
        ]);
    }

    private function fakeSuccessfulIntegrations(): void
    {
        $this->fakeFederatedIdentity();

        $this->mock(SoapAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('submit')->andReturn('IAE-LOG-2026-TEST');
        });

        $this->mock(RabbitMqPublisher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('publish')->andReturn(['status' => 'success']);
        });
    }

    private function fakeFederatedIdentity(string $roleName = 'hr_admin'): void
    {
        $role = Role::create(['name' => $roleName]);
        $user = FederatedUser::create([
            'role_id' => $role->id,
            'sso_subject' => 'warga01@ktp.iae.id',
            'name' => 'Ahmad Rizki Pratama',
            'email' => 'warga01@ktp.iae.id',
            'nim' => '2026000001',
        ])->setRelation('role', $role);

        $this->mock(SsoService::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('verifyUserToken')->andReturn([
                'iss' => 'iae-central-mock',
                'sub' => $user->sso_subject,
                'token_type' => 'user',
            ]);
            $mock->shouldReceive('mapLocalUser')->andReturn($user);
            $mock->shouldReceive('machineToken')->andReturn('test-m2m-token');
        });
    }
}
