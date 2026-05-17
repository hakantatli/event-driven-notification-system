Event-Driven Notification System Plan

1. Background & Motivation
Insider One requires a highly scalable and reliable notification system capable of processing millions of messages per day across multiple channels (SMS, Email, Push). The system must  │
handle burst traffic, execute intelligent retries, guarantee no data loss, and provide real-time observability and delivery tracking.

2. Scope & Impact
- Core APIs: Notification creation (single & batch), status tracking, cancellation, and paginated listing.
- Processing Engine: Asynchronous job processing with priority queues and rate limiting (max 100 msgs/sec/channel).
- External Integration: Simulated delivery via webhook.site.
- Bonus Features included: Scheduled notifications, Template System with variable substitution, and Real-time WebSocket updates.
- Infrastructure: Dockerized setup with PostgreSQL for persistent storage and Redis + Laravel Horizon for queue management and observability.

3. Proposed Solution
Technology Stack:
- PHP 8.x / Laravel 13
- Database: PostgreSQL
- Queue & Rate Limiting: Redis + Laravel Horizon
- WebSockets: Laravel Reverb
- Deployment: Docker Compose (Laravel Sail)

Architecture Details:
- API Layer: Exposes RESTful endpoints. Uses Form Requests for validation.
- Notification Manager: Handles persisting notifications to PostgreSQL and dispatching jobs to the Redis queue based on priority and schedule.
- Template Engine: A service that parses text templates and replaces variables (e.g., Hello {{name}}) before dispatching.
- Queue Workers: Laravel Horizon will manage workers, consuming the queue with specific rate limiting configurations (using Redis::throttle).
- Idempotency: Requests will be checked against unique identifiers (or content hashing) within a specific timeframe to prevent duplicate dispatches.
- Webhook Integration: An HTTP client service will send POST requests to the webhook URL. It will handle retries with exponential backoff on failures.
- Observability: Laravel Horizon provides a dashboard for queue metrics. Structured logging will be implemented using Monolog with correlation IDs. A /health endpoint will be provided.

Data Model (PostgreSQL):
- notifications: id, batch_id, channel, recipient, content, status (pending, processing, completed, failed, cancelled), priority, scheduled_at, error_log, created_at, updated_at
- templates: id, name, content, channel, created_at, updated_at

4. Alternatives Considered
- RabbitMQ vs. Redis: Considered RabbitMQ for its native persistence guarantees. However, Redis + Laravel Horizon was selected because of its seamless integration, built-in
observability dashboard, native support for rate-limiting, and priority queuing. Data loss risk is mitigated by configuring Redis persistence (AOF).

5. Implementation Plan

Start by initializing git. Commit each phase with convetional commits

- Phase 1: Setup & Infrastructure
    - Initialize Laravel 13 project via standard setup.
    - Configure Docker Compose (PostgreSQL, Redis) using Laravel Sail.
    - Setup API Routes and Swagger/OpenAPI documentation.
- Phase 2: Core Models & API
    - Create database migrations and Eloquent models for Notifications and Templates.
    - Implement the Template processing service.
    - Implement REST controllers for creating, listing, cancelling, and querying notifications.
- Phase 3: Queue & Processing Engine
    - Install and configure Laravel Horizon.
    - Create the ProcessNotification job.
    - Implement priority queues and Redis-based rate limiting (100 req/sec/channel).
    - Implement idempotency keys for the batch creation endpoints.
    - Implement the Template processing service.
    - Implement priority queues and Redis-based rate limiting (100 req/sec/channel).
    - Implement idempotency keys for the batch creation endpoints.
- Phase 4: External Integration & Retries
    - Implement the Webhook delivery service using Laravel's HTTP Client.
    - Configure automatic retries with exponential backoff on failure.
    - Update the database status based on the webhook response.
- Phase 5: Real-time & Observability
    - Setup Laravel Reverb for WebSocket broadcasting of status updates.
    - Implement the /health endpoint and metrics dashboard.
    - Configure structured logging with correlation IDs.
- Phase 6: Testing & CI/CD
    - Write unit and feature tests covering APIs, Queue Jobs, and the Template Engine.
    - Create GitHub Actions workflow for automated testing.

6. Verification & Testing
- Test-Driven Approach: We will write tests continuously alongside feature development.
- Unit Tests: Ensure 100% logic coverage for all isolated services (Template parsing, Rate limiting, Payload validation).
- Feature Tests: End-to-end API testing using Laravel's RefreshDatabase for creating, tracking, and cancelling notifications.
- Queue/Integration Tests: Verify priority queuing, idempotency controls, and scheduled dispatching logic via mocked queues (Queue::fake()).
- External Webhook Tests: Mock external HTTP calls using Http::fake() to strictly verify retry logic, timeout handling, and accurate database state updates upon failure or success.
- Run Tests locally: Ensure the entire test suite is runnable with a single command (e.g., php artisan test).

7. Migration & Rollback
- Database schema changes will be strictly versioned using Laravel Migrations.
- Rollback can be performed using php artisan migrate:rollback.
