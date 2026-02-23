# Order Processing API System

A Laravel 11 REST API demonstrating four key topics from **MIT 503 — Application Design and Integration**:

1. **API Design** — RESTful conventions, versioning, validation, resource transformation
2. **Service-Oriented Architecture (SOA)** — Focused services with single responsibilities
3. **System Integration Techniques** — Events, listeners, webhooks, sync/async patterns
4. **Middleware & Message Queues** — Request logging, async jobs, retry logic, dead-letter queue

## Architecture Overview

```
HTTP Request
    │
    ▼
┌─────────────────────┐
│  LogApiRequest       │  ← Middleware: logs method, URL, IP, status
│  Middleware          │
└─────────┬───────────┘
          ▼
┌─────────────────────┐
│  OrderController     │  ← API Design: thin controller, injected services
│  (routes/api.php)    │
└─────────┬───────────┘
          ▼
┌─────────────────────┐
│  OrderService        │  ← SOA: orchestrates the workflow
│  ├─ InventoryService │  ← SOA: synchronous stock check
│  └─ NotificationSvc  │  ← SOA: prepares payloads
└─────────┬───────────┘
          │
    ┌─────┴──────────────────────────┐
    │              │                  │
    ▼              ▼                  ▼
┌────────┐  ┌───────────┐  ┌──────────────┐
│ Queue  │  │  Event:   │  │  Webhook:    │
│ Jobs   │  │  Order    │  │  HTTP POST   │
│ (async)│  │  Placed   │  │  to external │
└───┬────┘  └─────┬─────┘  └──────────────┘
    │             │
    ▼             ▼
┌────────────┐  ┌──────────────────┐
│ SendOrder  │  │ SendNotification │
│ Confirm    │  │ Listener         │
│ Job        │  │ (logs event)     │
├────────────┤  └──────────────────┘
│ Update     │
│ Inventory  │
│ Job        │
└────────────┘
```

## Tech Stack

- **Laravel 11** (PHP 8.4)
- **MySQL 8** (via shared Docker container)
- **Queue Driver:** database (no Redis required)
- **Docker** for containerized development

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/OrderController.php   ← Thin controller
│   ├── Middleware/LogApiRequest.php              ← API logging
│   ├── Requests/StoreOrderRequest.php           ← Validation
│   └── Resources/OrderResource.php              ← JSON transformation
├── Services/
│   ├── OrderService.php                         ← Business logic orchestrator
│   ├── InventoryService.php                     ← Stock checks (sync)
│   └── NotificationService.php                  ← Payload preparation
├── Jobs/
│   ├── SendOrderConfirmationJob.php             ← Async email simulation
│   ├── UpdateInventoryJob.php                   ← Async inventory logging
│   └── ForceFailJob.php                         ← Dead-letter queue demo
├── Events/OrderPlaced.php                       ← Event (fire-and-forget)
├── Listeners/SendNotificationListener.php       ← Reacts to OrderPlaced
└── Models/
    ├── Order.php
    └── InventoryLog.php
```

## Setup Instructions

### 1. Clone the repository

```bash
git clone git@github.com:jonasivy/mit-503-laravel-demo.git
cd mit-503-laravel-demo
```

### 2. Environment configuration

```bash
cp .env.example .env
```

The `.env` is pre-configured for the Docker setup. Key settings:
- `DB_CONNECTION=mysql` with Docker MySQL credentials
- `QUEUE_CONNECTION=database`
- `WEBHOOK_URL=http://localhost:8000/api/v1/webhook-test`

### 3. Start Docker containers

```bash
docker compose up -d --build
```

This starts the Laravel app container on port 8000, connected to the existing MySQL container.

### 4. Install dependencies and run migrations

```bash
docker exec order-api-app composer install
docker exec order-api-app php artisan migrate --force
docker exec order-api-app php artisan db:seed
```

### 5. Start the queue worker (separate terminal)

```bash
docker exec order-api-app php artisan queue:work --tries=3
```

### 6. Test the API

```bash
curl http://localhost:8000/api/v1/orders
```

## API Endpoints

All routes are versioned under `/api/v1/`.

### Create Order
```bash
POST /api/v1/orders
Content-Type: application/json

{
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "item": "laptop",
    "quantity": 2,
    "total_price": 2499.98
}

# Response: 201 Created
```

### List Orders (with pagination)
```bash
GET /api/v1/orders?page=1&limit=10

# Response: 200 OK (paginated)
```

### Get Single Order
```bash
GET /api/v1/orders/1

# Response: 200 OK | 404 Not Found
```

### Update Order Status
```bash
PATCH /api/v1/orders/1
Content-Type: application/json

{
    "status": "confirmed"
}

# Response: 200 OK (status: pending | confirmed | failed)
```

### Webhook Test Endpoint
```bash
POST /api/v1/webhook-test
Content-Type: application/json

{
    "event": "order.placed",
    "order_id": 1
}

# Response: 200 OK — payload logged to notifications.log
```

## Available Items for Orders

The simulated inventory supports these items:
`laptop`, `phone`, `tablet`, `monitor`, `keyboard`, `mouse`, `headset`

Ordering an unknown item or exceeding available stock returns `422 Unprocessable Entity`.

## Demo Walkthrough

### Step 1: Show API Design (REST + Validation)

```bash
# List seeded orders
curl http://localhost:8000/api/v1/orders | jq

# Create a new order (201 Created)
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"Demo User","customer_email":"demo@test.com","item":"phone","quantity":1,"total_price":799.99}' | jq

# Validation error (422 — missing fields)
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{}' | jq

# Get single order
curl http://localhost:8000/api/v1/orders/1 | jq

# Update status
curl -X PATCH http://localhost:8000/api/v1/orders/1 \
  -H "Content-Type: application/json" \
  -d '{"status":"confirmed"}' | jq
```

### Step 2: Show SOA (Service Layer)

Point to the code:
- `OrderController` — thin, ~8 lines per method, no DB access
- `OrderService` — orchestrates: calls InventoryService → saves → dispatches jobs → fires event → sends webhook
- `InventoryService` — synchronous stock check before order is saved
- `NotificationService` — prepares payloads without performing I/O

```bash
# Out-of-stock rejection (422 — InventoryService rejects synchronously)
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_name":"Test","customer_email":"t@t.com","item":"unicorn","quantity":1,"total_price":99.99}' | jq
```

### Step 3: Show System Integration (Events + Webhook)

```bash
# Create an order — triggers event + webhook
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"Integration Demo","customer_email":"int@test.com","item":"tablet","quantity":1,"total_price":499.99}' | jq

# Check event listener wrote to notifications.log
docker exec order-api-app cat storage/logs/notifications.log

# Webhook payload was received by /api/v1/webhook-test (logged in same file)
```

### Step 4: Show Middleware & Message Queues

```bash
# Check middleware logging (every API call is logged)
docker exec order-api-app cat storage/logs/api_requests.log

# Check queue processed the jobs (in separate terminal running queue:work)
docker exec order-api-app cat storage/logs/notifications.log

# Check inventory_logs table was populated by UpdateInventoryJob
docker exec order-api-app php artisan tinker --execute="print_r(App\Models\InventoryLog::all()->toArray());"

# Demo dead-letter queue: dispatch ForceFailJob
docker exec order-api-app php artisan tinker --execute="App\Jobs\ForceFailJob::dispatch();"

# Watch it fail 3 times then land in failed_jobs
docker exec order-api-app php artisan queue:failed
```

## Log Files

| Log File | Purpose |
|---|---|
| `storage/logs/laravel.log` | General application logs |
| `storage/logs/api_requests.log` | LogApiRequest middleware output |
| `storage/logs/notifications.log` | Job confirmations + event notifications + webhook payloads |

## Topic-to-Code Mapping

| Course Topic | Files |
|---|---|
| **API Design** | `routes/api.php`, `OrderController`, `StoreOrderRequest`, `OrderResource` |
| **SOA** | `OrderService`, `InventoryService`, `NotificationService`, `AppServiceProvider` |
| **System Integration** | `OrderPlaced` event, `SendNotificationListener`, webhook in `OrderService`, `routes/api.php` (webhook-test) |
| **Middleware & Queues** | `LogApiRequest`, `SendOrderConfirmationJob`, `UpdateInventoryJob`, `ForceFailJob`, `config/logging.php` |
