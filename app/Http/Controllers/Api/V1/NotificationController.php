<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->listForUser($request->user());

        return $this->paginated($notifications, NotificationResource::class);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $updated = $this->notificationService->markRead($notification);

        return $this->success(new NotificationResource($updated), 'Notification marquée comme lue.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllRead($request->user());

        return $this->success(['updated' => $count], 'Notifications marquées comme lues.');
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->delete($notification);

        return $this->success(null, 'Notification supprimée avec succès.');
    }
}
