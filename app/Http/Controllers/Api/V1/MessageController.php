<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly MessageService $messageService) {}

    public function conversations(Request $request): JsonResponse
    {
        $conversations = $this->messageService->listConversations($request->user());

        return $this->success(ConversationResource::collection($conversations), '');
    }

    public function messages(Request $request, User $conversation): JsonResponse
    {
        $thread = $this->messageService->messagesForConversation($request->user(), $conversation->id);

        return $this->paginated($thread, MessageResource::class);
    }

    public function store(StoreMessageRequest $request): JsonResponse
    {
        $message = $this->messageService->send($request->user(), $request->validated());

        return $this->success(new MessageResource($message), 'Message envoyé avec succès.', 201);
    }

    public function markRead(Request $request, Message $message): JsonResponse
    {
        $this->authorize('markRead', $message);

        $updated = $this->messageService->markRead($message);

        return $this->success(new MessageResource($updated), 'Message marqué comme lu.');
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->messageService->delete($message);

        return $this->success(null, 'Message supprimé avec succès.');
    }
}
