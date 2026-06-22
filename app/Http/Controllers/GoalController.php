<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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
        Cache::forget('dashboard_data_user_' . $request->user()->id);

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
        Gate::authorize('addFunds', $goal);

        Cache::forget('dashboard_data_user_' . $request->user()->id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->goalService->addFunds($goal, (float) $validated['amount']);

        $request->user()->addXp(25);

        return redirect()->back();
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        Gate::authorize('delete', $goal);

        Cache::forget('dashboard_data_user_' . auth()->id());

        $goal->delete();

        return redirect()->back();
    }
}