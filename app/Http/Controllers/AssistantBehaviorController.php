<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AssistantBehavior;

class AssistantBehaviorController extends Controller
{
    public function index()
    {
        return Inertia::render('Behavior/Index', [
            'behavior' => auth()->user()->behavior
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'behavior' => 'required|string|max:2000'
        ]);

        auth()->user()->behavior()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['behavior' => $request->behavior]
        );

        return redirect()->back()->with('success', 'Comportement mis à jour avec succès');
    }
}
