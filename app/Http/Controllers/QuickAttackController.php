<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class QuickAttackController extends Controller
{
    /**
     * Record a quick expense (Quick Attack) and reward the user with XP.
     */
    public function store(Request $request): RedirectResponse
    {
        Cache::forget('dashboard_data_user_' . $request->user()->id);

        $validated = $request->validate([
            'monto'       => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['required', 'string', 'max:255'],
        ]);

        Expense::create([
            'user_id'     => $request->user()->id,
            'amount'      => $validated['monto'],
            'description' => $validated['descripcion'],
        ]);

        // Award XP for logging an expense — keeps the Commander engaged.
        $request->user()->addXp(15);

        return redirect()
            ->back()
            ->with('success', '¡Suministro registrado! +15 XP');
    }
}
