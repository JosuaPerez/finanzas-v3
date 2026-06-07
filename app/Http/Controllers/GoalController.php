<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function __construct(private readonly GoalService $goalService) {}

    public function index(): Response
    {
        return Inertia::render('Metas', [
            'goals' => auth()->user()->goals()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'target_amount'  => 'required|numeric|min:0.01',
            'current_amount' => 'nullable|numeric|min:0',
            'currency'       => 'required|string|in:DOP,USD',
            'deadline'       => 'nullable|date',
        ]);

        $request->user()->goals()->create($validated);

        return redirect()->back();
    }

    public function addFunds(Request $request, Goal $goal): RedirectResponse
    {
        // Authorization: only the owner may fund their own goal
        if ($goal->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->goalService->addFunds($goal, (float) $validated['amount']);

        return redirect()->back();
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        if ($goal->user_id !== auth()->id()) {
            abort(403);
        }

        $goal->delete();

        return redirect()->back();
    }
}