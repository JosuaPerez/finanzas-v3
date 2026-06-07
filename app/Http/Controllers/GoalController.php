<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalController extends Controller
{
    public function index()
    {
        return Inertia::render('Metas', [
            'goals' => auth()->user()->goals()->get(),
            // Nota: Aquí deberías calcular el capital libre igual que en el Dashboard 
            // para mostrar la 'ammunition' correctamente.
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric',
            'current_amount' => 'nullable|numeric',
            'currency' => 'required|string',
            'deadline' => 'nullable|date',
        ]);

        $request->user()->goals()->create($validated);

        return redirect()->back();
    }

    public function addFunds(Request $request, Goal $goal)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Incrementamos el monto actual
        $goal->increment('current_amount', $validated['amount']);

        return redirect()->back();
    }

    public function destroy(Goal $goal)
    {
        // Aseguramos que el usuario solo borre sus propias metas
        if ($goal->user_id !== auth()->id()) {
            abort(403);
        }
        
        $goal->delete();
        return redirect()->back();
    }
}