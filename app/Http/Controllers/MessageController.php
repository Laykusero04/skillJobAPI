<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * List messages for a conversation.
     */
    public function index(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (!$conversation->isParticipant($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $perPage = $request->input('per_page', 30);

        $query = $conversation->messages()
            ->withTrashed()
            ->with('sender:id,first_name,last_name')
            ->orderByDesc('created_at');

        // Cursor pagination: load messages before a specific ID
        if ($request->filled('before_id')) {
            $query->where('id', '<', $request->before_id);
        }

        $messages = $query->paginate($perPage);

        $messages->getCollection()->transform(function ($message) use ($user) {
            return $this->formatMessage($message, $user);
        });

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a message.
     */
    public function store(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (!$conversation->isParticipant($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender:id,first_name,last_name');

        return response()->json([
            'data' => $this->formatMessage($message, $user),
        ], 201);
    }

    /**
     * Edit a message.
     */
    public function update(Request $request, Conversation $conversation, Message $message)
    {
        $user = $request->user();

        if (!$conversation->isParticipant($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($message->conversation_id !== $conversation->id) {
            return response()->json(['message' => 'Message does not belong to this conversation.'], 404);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'You can only edit your own messages.'], 403);
        }

        // Check 15-minute edit window
        if ($message->created_at->lt(now()->subMinutes(15))) {
            return response()->json(['message' => 'Edit window has expired. Messages can only be edited within 15 minutes.'], 403);
        }

        if ($message->trashed()) {
            return response()->json(['message' => 'Cannot edit a deleted message.'], 422);
        }

        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update([
            'body' => $request->body,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        $message->load('sender:id,first_name,last_name');

        return response()->json([
            'data' => $this->formatMessage($message, $user),
        ]);
    }

    /**
     * Soft-delete a message.
     */
    public function destroy(Request $request, Conversation $conversation, Message $message)
    {
        $user = $request->user();

        if (!$conversation->isParticipant($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($message->conversation_id !== $conversation->id) {
            return response()->json(['message' => 'Message does not belong to this conversation.'], 404);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'You can only delete your own messages.'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted.']);
    }

    /**
     * Format a message for the API response.
     */
    private function formatMessage(Message $message, $currentUser): array
    {
        $isMe = $message->sender_id === $currentUser->id;
        $canEdit = $isMe && !$message->trashed() && $message->created_at->gte(now()->subMinutes(15));
        $canDelete = $isMe && !$message->trashed();

        return [
            'id' => $message->id,
            'body' => $message->trashed() ? null : $message->body,
            'sender_id' => $message->sender_id,
            'is_me' => $isMe,
            'sent_at' => $message->created_at->toIso8601String(),
            'is_edited' => $message->is_edited,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'deleted_at' => $message->deleted_at?->toIso8601String(),
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ];
    }
}
