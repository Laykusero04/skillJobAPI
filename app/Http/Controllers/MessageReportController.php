<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReport;
use Illuminate\Http\Request;

class MessageReportController extends Controller
{
    /**
     * Report a conversation or message.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reportable_type' => ['required', 'string', 'in:conversation,message'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'in:spam,harassment,inappropriate,scam,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // Resolve the reportable and verify the reporter is a participant
        if ($request->reportable_type === 'conversation') {
            $conversation = Conversation::find($request->reportable_id);

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found.'], 404);
            }

            if (!$conversation->isParticipant($user->id)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            $reportableType = Conversation::class;
        } else {
            $message = Message::find($request->reportable_id);

            if (!$message) {
                return response()->json(['message' => 'Message not found.'], 404);
            }

            $conversation = $message->conversation;

            if (!$conversation->isParticipant($user->id)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            // Cannot report your own message
            if ($message->sender_id === $user->id) {
                return response()->json(['message' => 'You cannot report your own message.'], 422);
            }

            $reportableType = Message::class;
        }

        $report = MessageReport::create([
            'reporter_id' => $user->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $request->reportable_id,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $report], 201);
    }
}
