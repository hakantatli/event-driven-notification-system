# Event-Driven Notification System

A scalable notification system built with Laravel 13, PostgreSQL, Redis, and Horizon.

## Features
- **Scalable APIs**: Create single or batch notifications (up to 1000).
- **Asynchronous Processing**: Powered by Laravel Horizon with priority queues (high, normal, low).
- **Rate Limiting**: Strict limit of 100 messages/second/channel using Redis.
- **Template System**: Dynamic templates with variable substitution.
- **Scheduled Notifications**: Delay delivery for a specific time.
- **Real-time Updates**: Status changes broadcasted via Laravel Reverb.
- **Observability**: Built-in Horizon dashboard and a custom `/api/health` endpoint.
- **Structured Logging**: Correlation IDs included in all logs and response headers.
- **Idempotency**: Prevent duplicate notifications using `idempotency_key`.

## Tech Stack
- **Framework**: Laravel 13
- **Database**: PostgreSQL
- **Queue/Cache**: Redis + Laravel Horizon
- **WebSockets**: Laravel Reverb
- **Infrastructure**: Docker Compose (Laravel Sail)

## Setup

1. **Clone the repository**
2. **Install dependencies**:
   ```bash
   composer install
   ```
3. **Set environment variables**:
   ```bash
   cp .env.example .env
   # Update NOTIFICATION_PROVIDER_URL and other credentials
   ```
4. **Start the system**:
   ```bash
   ./vendor/bin/sail up -d
   ```
5. **Run migrations**:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
6. **Start Horizon (in a separate tab or as background)**:
   ```bash
   ./vendor/bin/sail artisan horizon
   ```

## API Documentation
Once the system is running, access the Swagger documentation at:
`http://localhost:8001/api/documentation`

## Testing
Run the test suite with a single command:
```bash
./vendor/bin/sail artisan test
```

## Monitoring
- **Horizon Dashboard**: `http://localhost:8001/horizon`
- **Health Check**: `GET http://localhost:8001/api/health`
