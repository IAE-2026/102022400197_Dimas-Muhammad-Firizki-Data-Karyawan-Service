# 102022400197_Dimas-Muhammad-Firizki-Data-Karyawan-Service

## Data Karyawan Service

Laravel service for the IAE Assignment 2 `Penggajian Karyawan` project. This repository owns employee master data and exposes it through REST and GraphQL APIs.

## Service Identity

- Domain: Penggajian Karyawan
- Service: Data Karyawan
- Owner NIM/API key: `102022400197`
- Repository format: `102022400197_Dimas-Muhammad-Firizki-Data-Karyawan-Service`
- Docker app URL: `http://localhost:8001`
- Docker service name: `employee-service`
- Database service name: `employee-db`

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

## GraphQL

- Endpoint: `POST /graphql`
- GraphiQL page: `GET /graphiql`

Example:

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

## Documentation

- Swagger UI: `GET /docs`
- OpenAPI JSON: `GET /openapi.json`

## Docker

```bash
cp .env.example .env
docker compose up --build
```

The API runs at:

```text
http://localhost:8001
```

## Tests

```bash
php artisan test
```

The tests cover REST endpoints, API key protection, validation, Swagger/OpenAPI availability, and GraphQL availability.
