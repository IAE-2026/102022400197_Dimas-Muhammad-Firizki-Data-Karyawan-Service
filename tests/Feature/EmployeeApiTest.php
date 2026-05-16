<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = '102022400197';

    public function test_can_create_and_list_employees(): void
    {
        $create = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->postJson('/api/v1/employees', $this->payload());

        $create->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.employee_id', 'EMP-001')
            ->assertJsonPath('meta.service_name', 'Data-Karyawan-Service');

        $list = $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->getJson('/api/v1/employees');

        $list->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.name', 'Dimas Pratama');
    }

    public function test_can_get_employee_detail(): void
    {
        $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->postJson('/api/v1/employees', $this->payload());

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
        $response = $this->withHeader('X-IAE-KEY', self::API_KEY)
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
        $this->withHeader('X-IAE-KEY', self::API_KEY)
            ->postJson('/api/v1/employees', $this->payload());

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
}
