<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * List conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);

        $query = Conversation::where('employer_id', $user->id)
            ->orWhere('freelancer_id', $user->id);

        $query->with(['employer:id,first_name,last_name,profile_image_url', 'freelancer:id,first_name,last_name,profile_image_url', 'gig:id,title,start_at,end_at,pay']);

        if ($request->boolean('unread_only')) {
            $query->whereHas('messages', function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)
                    ->where('created_at', '>', DB::raw(
                        "(SELECT COALESCE(last_read_at, '1970-01-01') FROM conversation_user WHERE conversation_user.conversation_id = conversations.id AND conversation_user.user_id = {$user->id})"
                    ));
            });
        }

        $conversations = $query->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            return $this->formatConversation($conversation, $user);
        });

        return response()->json(['data' => $conversations]);
    }

    /**
     * Create or get existing conversation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gig_id' => ['nullable', 'integer', 'exists:gigs,id'],
            'other_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $otherUser = User::findOrFail($request->other_user_id);

        if ($user->id === $otherUser->id) {
            return response()->json(['message' => 'Cannot create a conversation with yourself.'], 422);
        }

        // Determine employer and freelancer
        if ($user->role === 2 && $otherUser->role === 3) {
            $employerId = $user->id;
            $freelancerId = $otherUser->id;
        } elseif ($user->role === 3 && $otherUser->role === 2) {
            $employerId = $otherUser->id;
            $freelancerId = $user->id;
        } else {
            return response()->json(['message' => 'Conversations must be between an employer and a freelancer.'], 422);
        }

        // Check for existing conversation
        $existing = Conversation::where('employer_id', $employerId)
            ->where('freelancer_id', $freelancerId)
            ->where('gig_id', $request->gig_id)
            ->first();

        if ($existing) {
            $existing->load(['employer:id,first_name,last_name,profile_image_url', 'freelancer:id,first_name,last_name,profile_image_url', 'gig:id,title,start_at,end_at,pay']);

            return response()->json([
                'data' => $this->formatConversation($existing, $user),
            ]);
        }

        // Create new conversation
        $conversation = Conversation::create([
            'gig_id' => $request->gig_id,
            'employer_id' => $employerId,
            'freelancer_id' => $freelancerId,
        ]);

        // Create pivot rows for both participants
        $conversation->participants()->attach([
            $employerId => ['last_read_at' => now()],
            $freelancerId => ['last_read_at' => now()],
        ]);

        $conversation->load(['employer:id,first_name,last_name,profile_image_url', 'freelancer:id,first_name,last_name,profile_image_url', 'gig:id,title,start_at,end_at,pay']);

        return response()->json([
            'data' => $this->formatConversation($conversation, $user),
        ], 201);
    }

    /**
     * Mark conversation as read for the current user.
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (!$conversation->isParticipant($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        return response()->json(['message' => 'Conversation marked as read.']);
    }

    /**
     * Format a conversation for the API response.
     */
    private function formatConversation(Conversation $conversation, $currentUser): array
    {
        $isEmployer = $conversation->employer_id === $currentUser->id;
        $otherUser = $isEmployer ? $conversation->freelancer : $conversation->employer;

        // Get last message
        $lastMessage = $conversation->messages()
            ->withTrashed()
            ->orderByDesc('created_at')
            ->first();

        // Get unread count
        $pivot = DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $currentUser->id)
            ->first();

        $lastReadAt = $pivot?->last_read_at ?? '1970-01-01';

        $unreadCount = $conversation->messages()
            ->where('sender_id', '!=', $currentUser->id)
            ->where('created_at', '>', $lastReadAt)
            ->count();

        // Format gig info
        $gig = $conversation->gig;
        $gigSchedule = null;
        $gigPay = null;

        if ($gig) {
            $gigSchedule = $gig->start_at->format('M d, g:i A') . ' - ' . $gig->end_at->format('g:i A');
            $gigPay = '€' . number_format((float) $gig->pay, 0);
        }

        // Build initials
        $initials = '';
        if ($otherUser) {
            $initials = strtoupper(substr($otherUser->first_name, 0, 1));
            if ($otherUser->last_name) {
                $initials .= strtoupper(substr($otherUser->last_name, 0, 1));
            }
        }

        return [
            'id' => $conversation->id,
            'gig_id' => $conversation->gig_id,
            'gig_title' => $gig?->title,
            'gig_schedule' => $gigSchedule,
            'gig_pay' => $gigPay,
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => trim($otherUser->first_name . ' ' . $otherUser->last_name),
                'initials' => $initials,
                'avatar_url' => $otherUser->profile_image_url,
            ] : null,
            'last_message' => $lastMessage ? [
                'body' => $lastMessage->deleted_at ? null : $lastMessage->body,
                'sent_at' => $lastMessage->created_at->toIso8601String(),
                'is_from_me' => $lastMessage->sender_id === $currentUser->id,
            ] : null,
            'unread_count' => $unreadCount,
            'updated_at' => $conversation->updated_at->toIso8601String(),
        ];
    }
}
