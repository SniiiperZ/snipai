<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\UserInstruction;

class UserInstructionController extends Controller
{
    public function index()
    {
        return Inertia::render('Instructions/Index', [
            'instructions' => auth()->user()->instructions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:2000'
        ]);

        auth()->user()->instructions()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['content' => $request->content]
        );

        return redirect()->back()->with('success', 'Instructions mises à jour avec succès');
    }
}
