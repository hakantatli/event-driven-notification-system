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

    public function store(StoreNotificationRequest $request)
    {
        $notification = $this->notificationService->createNotification($request->validated());
        return response()->json([
            'messageId' => $notification->id,
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202);
    }

    public function batchStore(StoreBatchNotificationRequest $request)
    {
        $result = $this->notificationService->createBatch($request->validated()['notifications']);
        return response()->json([
            'messageId' => $result['batch_id'],
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202);
    }

    public function show(Notification $notification)
    {
        return response()->json($notification);
    }

    public function cancel(Notification $notification)
    {
        if ($notification->status !== 'pending') {
            return response()->json(['message' => 'Only pending notifications can be cancelled'], 422);
        }

        $notification->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Notification cancelled successfully']);
    }
}
