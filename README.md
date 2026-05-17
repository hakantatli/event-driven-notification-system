# Event-Driven Notification System

A scalable notification system built with Laravel 13, PostgreSQL, Redis, and Horizon.

## Features
- **Scalable APIs**: Create single or batch notifications (up to 1000).
- **Asynchronous Processing**: Powered by Laravel Horizon with priority queues (high, normal, low).
- **Rate Limiting**: Configurable via ENV (default 100 msgs/sec/channel) using Redis.
- **Template System**: Dynamic templates with variable substitution.
- **Scheduled Notifications**: Delay delivery for a specific time.
- **Real-time Updates**: Status changes broadcasted via Laravel Reverb (asynchronous).
- **Observability**: Built-in Horizon dashboard and a custom `/api/health` endpoint.
- **Structured Logging**: Correlation IDs included in all logs and response headers.
- **Idempotency**: Prevent duplicate notifications using `idempotency_key` with database-level uniqueness.

## Tech Stack
- **Framework**: Laravel 13
- **Database**: PostgreSQL
- **Queue/Cache**: Redis + Laravel Horizon
- **WebSockets**: Laravel Reverb
- **Infrastructure**: Docker Compose (Laravel Sail)

## Quick Start

1. **Clone the repository**
2. **Setup the project**:
   ```bash
   make setup
   ```
3. **Start the system**:
   ```bash
   make up
   ```

## Makefile Commands

The following shortcuts are available via the `Makefile` for easier development:

| Command | Description |
|---------|-------------|
| `make up` | Start the Docker containers in detached mode |
| `make down` | Stop all Docker containers |
| `make restart` | Full stop and restart of the system |
| `make test` | Run the full PHPUnit test suite |
| `make load-test`| Run high-volume load test (1k notifications) |
| `make horizon` | Start the Horizon dashboard and queue workers |
| `make migrate` | Run database migrations |
| `make shell` | Open a bash shell inside the application container |
| `make logs` | Follow the application and container logs |
| `make setup` | Initial project installation and configuration |

## API Documentation
Once the system is running, access the Swagger documentation at:
`http://localhost:8001/api/documentation`

### 1. Create Single Notification
**POST** `/api/v1/notifications`
```bash
curl -X POST http://localhost:8001/api/v1/notifications \
     -H "Content-Type: application/json" \
     -d '{
           "channel": "sms",
           "recipient": "+905000000000",
           "content": "Hello World",
           "priority": "high",
           "idempotency_key": "unique-uuid-here"
         }'
```

### 2. Create Batch Notifications
**POST** `/api/v1/notifications/batch` (Limit: 1000)
```bash
curl -X POST http://localhost:8001/api/v1/notifications/batch \
     -H "Content-Type: application/json" \
     -d '{
           "notifications": [
             {"channel": "email", "recipient": "user1@example.com", "content": "Msg 1"},
             {"channel": "push", "recipient": "token_abc", "content": "Msg 2"}
           ]
         }'
```

### 3. List & Filter Notifications
**GET** `/api/v1/notifications`
| Parameter | Description |
|-----------|-------------|
| `status`  | `pending`, `processing`, `completed`, `failed`, `cancelled` |
| `channel` | `sms`, `email`, `push` |
| `batch_id`| UUID of the batch |
| `date_from`| YYYY-MM-DD |

**Example (Find failed SMS):**
`GET /api/v1/notifications?status=failed&channel=sms`

### Response Format
All creation endpoints return **202 Accepted**:
```json
{
  "messageId": "uuid-here",
  "status": "accepted",
  "timestamp": "ISO8601"
}
```

## Monitoring
- **Horizon Dashboard**: `http://localhost:8001/horizon`
- **Health Check**: `GET http://localhost:8001/api/health`
- **Real-time Logs**: Open `client/index.html` in your browser to view live WebSocket status updates.
