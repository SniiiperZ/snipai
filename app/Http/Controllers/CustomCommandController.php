<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CustomCommand;

class CustomCommandController extends Controller
{
    public function index()
    {
        return Inertia::render('Commands/Index', [
            'commands' => auth()->user()->customCommands
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'command' => 'required|string|max:50|unique:custom_commands,command,' . $request->id,
            'description' => 'required|string|max:255',
            'action' => 'required|string|max:2000'
        ]);

        auth()->user()->customCommands()->updateOrCreate(
            ['id' => $request->id ?? null],
            [
                'command' => $request->command,
                'description' => $request->description,
                'action' => $request->action
            ]
        );

        return redirect()->back()->with('success', 'Commande mise à jour avec succès');
    }

    public function destroy($id)
    {
        $command = CustomCommand::findOrFail($id);
        abort_if($command->user_id !== auth()->id(), 403);
        
        $command->delete();
        return redirect()->back()->with('success', 'Commande supprimée avec succès');
    }
}
