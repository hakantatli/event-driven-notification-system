<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatchNotificationRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    #[OA\Get(
        path: "/notifications",
        summary: "List notifications with filtering and pagination",
        tags: ["Notifications"],
        parameters: [
            new OA\Parameter(name: "status", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "channel", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "batch_id", in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated list of notifications")
        ]
    )]
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    #[OA\Post(
        path: "/notifications",
        summary: "Create a single notification",
        tags: ["Notifications"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["channel", "recipient"],
                properties: [
                    new OA\Property(property: "channel", type: "string", enum: ["sms", "email", "push"]),
                    new OA\Property(property: "recipient", type: "string", example: "+905000000000"),
                    new OA\Property(property: "content", type: "string", example: "Hello World"),
                    new OA\Property(property: "template_name", type: "string", example: "welcome"),
                    new OA\Property(property: "template_vars", type: "object", example: ["name" => "John"]),
                    new OA\Property(property: "priority", type: "string", enum: ["low", "normal", "high"], default: "normal"),
                    new OA\Property(property: "scheduled_at", type: "string", format: "date-time", example: "2026-05-18 10:00:00"),
                    new OA\Property(property: "idempotency_key", type: "string", example: "uuid-here"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: "Accepted",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "messageId", type: "string", format: "uuid"),
                        new OA\Property(property: "status", type: "string", example: "accepted"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(StoreNotificationRequest $request)
    {
        $notification = $this->notificationService->createNotification($request->validated());
        return response()->json([
            'messageId' => $notification->id,
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202);
    }

    #[OA\Post(
        path: "/notifications/batch",
        summary: "Create batch notifications",
        tags: ["Notifications"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["notifications"],
                properties: [
                    new OA\Property(
                        property: "notifications",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "channel", type: "string", enum: ["sms", "email", "push"]),
                                new OA\Property(property: "recipient", type: "string"),
                                new OA\Property(property: "content", type: "string"),
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: "Accepted",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "messageId", type: "string", format: "uuid", description: "Batch ID"),
                        new OA\Property(property: "status", type: "string", example: "accepted"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time")
                    ]
                )
            )
        ]
    )]
    public function batchStore(StoreBatchNotificationRequest $request)
    {
        $result = $this->notificationService->createBatch($request->validated()['notifications']);
        return response()->json([
            'messageId' => $result['batch_id'],
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202);
    }

    #[OA\Get(
        path: "/notifications/{id}",
        summary: "Get notification details",
        tags: ["Notifications"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Notification details"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show(Notification $notification)
    {
        return response()->json($notification);
    }

    #[OA\Post(
        path: "/notifications/{id}/cancel",
        summary: "Cancel a pending notification",
        tags: ["Notifications"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Cancelled successfully"),
            new OA\Response(response: 422, description: "Cannot cancel non-pending notification")
        ]
    )]
    public function cancel(Notification $notification)
    {
        if ($notification->status !== 'pending') {
            return response()->json(['message' => 'Only pending notifications can be cancelled'], 422);
        }

        $notification->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Notification cancelled successfully']);
    }
}
