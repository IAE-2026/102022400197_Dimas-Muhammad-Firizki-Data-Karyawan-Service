# Data Karyawan Service

Laravel service for the IAE Assignment 2 `Penggajian Karyawan` project. This repository owns employee master data and exposes it through REST and GraphQL APIs.

## Service Identity

- Domain: Penggajian Karyawan
- Service: Data Karyawan
- Owner NIM/API key: `102022400197`
- Repository format: `102022400197_Nama-Data-Karyawan`
- Docker app URL: `http://localhost:8001`
- Docker service name: `employee-service`
- Database service name: `employee-db`

## Architecture

Each team member owns one independent service repository. This service owns only the `employees` table. Other services, such as Absensi Karyawan and Payroll Karyawan, must call this service through HTTP APIs and must not access this database directly.

Suggested flow:

1. HR Admin creates employee data in Data Karyawan Service.
2. Absensi Service validates `employee_id` and `status=active` by calling `GET /api/v1/employees/{id}`.
3. Payroll Service calls Data Karyawan and Absensi Service through HTTP before generating salary slips.

## REST API

All REST endpoints use JSON and require:

```http
X-IAE-KEY: 102022400197
```

Endpoints:

- `GET /api/v1/employees`
- `GET /api/v1/employees/{id}`
- `POST /api/v1/employees`
- `PUT /api/v1/employees/{id}`
- `DELETE /api/v1/employees/{id}`

Success format:

```json
{
  "status": "success",
  "message": "Operation successful",
  "data": {},
  "meta": {
    "service_name": "Data-Karyawan-Service",
    "api_version": "v1"
  }
}
```

Error format:

```json
{
  "status": "error",
  "message": "Detail pesan kesalahan...",
  "errors": null
}
```

## Employee Fields

- `employee_id`
- `nik`
- `name`
- `email`
- `position`
- `department`
- `base_salary`
- `fixed_allowance`
- `status`: `active`, `inactive`, or `resigned`

Example create request:

```bash
curl -X POST http://localhost:8001/api/v1/employees \
  -H "Content-Type: application/json" \
  -H "X-IAE-KEY: 102022400197" \
  -d "{\"employee_id\":\"EMP-001\",\"nik\":\"3276010101010001\",\"name\":\"Dimas Pratama\",\"email\":\"dimas@example.com\",\"position\":\"Staff HR\",\"department\":\"Human Resource\",\"base_salary\":5500000,\"fixed_allowance\":750000,\"status\":\"active\"}"
```

## GraphQL

Endpoint:

- `POST /graphql`

GraphiQL page:

- `GET /graphiql`

Example query:

```graphql
query {
  employees {
    employee_id
    name
    department
    status
  }
}
```

Single employee:

```graphql
query {
  employee(id: "EMP-001") {
    employee_id
    name
    base_salary
  }
}
```

Schema file:

- `graphql/schema.graphql`

## API Documentation

Swagger UI:

- `GET /docs`

OpenAPI JSON:

- `GET /openapi.json`

## Docker

Copy the example environment and start the service:

```bash
cp .env.example .env
docker compose up --build
```

The API will be available at:

```text
http://localhost:8001
```

## Local Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8001
```

For local SQLite development, set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite`.

## Tests

```bash
php artisan test
```

The test suite covers:

- REST create/list/detail endpoints
- API key protection
- validation error `422`
- not found `404`
- Swagger/OpenAPI availability
- GraphQL query availability

## Inter-Service Communication

When all team services run inside the same Docker network, other containers can call:

```text
http://employee-service:8000/api/v1/employees/{employee_id}
```

When services run on one laptop through host ports, call:

```text
http://localhost:8001/api/v1/employees/{employee_id}
```

When services run on different laptops, replace `localhost` with the host laptop IP address.
