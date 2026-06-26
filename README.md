# 102022400197_Dimas-Muhammad-Firizki-Data-Karyawan-Service

## Data Karyawan Service

Laravel service for the IAE `Penggajian Karyawan` project. The Tugas 3 flow integrates employee transactions with Federated SSO, Legacy SOAP Audit, and the central RabbitMQ publisher.

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

- `POST /api/v1/auth/login`
- `GET /api/v1/employees`
- `GET /api/v1/employees/{id}`
- `POST /api/v1/employees`
- `PUT /api/v1/employees/{id}`
- `DELETE /api/v1/employees/{id}`

For Tugas 2 compatibility, `POST /api/v1/employees` can create an employee with only `X-IAE-KEY`.

For the Tugas 3 critical transaction flow, send a Bearer token when creating, updating, or deleting employees:

```http
Authorization: Bearer <JWT_FROM_LOGIN>
```

When a Bearer token is supplied, only the local role `hr_admin` may run the critical flow. A successful critical change returns a SOAP `audit_receipt_number` and publishes an `employee.created`, `employee.updated`, or `employee.deleted` event. If either central integration fails, the local database transaction is rolled back and the API returns `502`. `PUT` and `DELETE` always require the Bearer token and role check.

## Central Integration Configuration

For Docker, the service boots from `.env.example` automatically. To run the full Tugas 3 central integration flow, fill the M2M key supplied by the lecturer in the environment used by Docker:

```env
IAE_M2M_API_KEY=KEY-MHS-XX
IAE_TEAM_ID=TEAM-09
```

The user SSO credentials are sent through `POST /api/v1/auth/login`. The service verifies the returned RS256 JWT against the central JWKS and maps the user into local `federated_users` and `roles` tables.

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
docker compose up --build -d
```

The API runs at:

```text
http://localhost:8001
```

Docker automatically waits for MySQL and runs all migrations. Confirm the containers with:

```bash
docker compose ps
docker compose logs -f employee-service
```

## Postman

Import:

```text
postman/Tugas-3-Employee-Service.postman_collection.json
```

Run the requests in order. `Login SSO` stores the JWT automatically in the collection variable, then the create/update/delete requests reuse it.

## Tugas 3 Analysis

The critical transaction justification, local role model, SOAP transformation, event contract, and sequence diagram are documented in:

```text
analisis_tugas_3.md
```

## Tests

```bash
php artisan test
```

The tests cover REST, API key protection, SSO/role enforcement, validation, SOAP receipt storage, RabbitMQ publishing, rollback behavior, Swagger/OpenAPI, and GraphQL.
