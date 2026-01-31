<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserSkillController extends Controller
{
    /**
     * List the authenticated freelancer's skills.
     */
    public function index(Request $request)
    {
        $skills = $request->user()->skills()->orderBy('name')->get();

        return response()->json($skills);
    }

    /**
     * Replace the freelancer's skills with the given set.
     * Accepts an array of skill_ids — full replacement (sync).
     */
    public function update(Request $request)
    {
        $request->validate([
            'skill_ids' => ['required', 'array', 'min:1'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $request->user()->skills()->sync($request->skill_ids);

        $skills = $request->user()->skills()->orderBy('name')->get();

        return response()->json($skills);
    }
}
