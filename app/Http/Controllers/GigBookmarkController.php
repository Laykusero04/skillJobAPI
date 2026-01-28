<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\GigBookmark;
use Illuminate\Http\Request;

class GigBookmarkController extends Controller
{
    /**
     * List bookmarked gigs for the current user.
     */
    public function index(Request $request)
    {
        $bookmarks = $request->user()
            ->gigBookmarks()
            ->with('gig.primarySkill', 'gig.supportingSkills', 'gig.employer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($bookmarks);
    }

    /**
     * Toggle bookmark on a gig.
     */
    public function toggle(Request $request, Gig $gig)
    {
        $user = $request->user();

        $existing = GigBookmark::where('user_id', $user->id)
            ->where('gig_id', $gig->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['bookmarked' => false]);
        }

        GigBookmark::create([
            'user_id' => $user->id,
            'gig_id' => $gig->id,
        ]);

        return response()->json(['bookmarked' => true]);
    }
}
