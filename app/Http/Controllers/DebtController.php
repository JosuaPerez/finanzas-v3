<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Services\DebtService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class DebtController extends Controller
{
    public function __construct(private readonly DebtService $debtService) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'balance'               => 'required|numeric|min:0',
            'interest_rate'         => 'required|numeric|min:0',
            'minimum_payment'       => 'required|numeric|min:0',
            'type'                  => 'required|string|in:loan,credit_card',
            'currency'              => 'required|string|in:DOP,USD',
            'credit_limit'          => 'nullable|numeric|min:0',
            'cutoff_date'           => 'nullable|integer|min:1|max:31',
            'payment_date'          => 'nullable|integer|min:1|max:31',
            'original_amount'       => 'nullable|numeric|min:0',
            'overdraft_percentage'  => 'nullable|numeric|min:0|max:100',
            'fecha_inicio'          => 'nullable|date',
            'plazo_original_meses'  => 'nullable|integer|min:1|max:600',
        ]);

        $request->user()->debts()->create([
            'name'                  => $request->name,
            'balance'               => $request->balance,
            'interest_rate'         => $request->interest_rate        ?? 0,
            'minimum_payment'       => $request->minimum_payment      ?? 0,
            'type'                  => $request->type                 ?? 'loan',
            'credit_limit'          => $request->credit_limit,
            'cutoff_date'           => $request->cutoff_date,
            'payment_date'          => $request->payment_date,
            'original_amount'       => $request->original_amount,
            'currency'              => $request->currency             ?? 'DOP',
            'overdraft_percentage'  => $request->overdraft_percentage ?? 0,
            'fecha_inicio'          => $request->fecha_inicio         ?: null,
            'plazo_original_meses'  => $request->plazo_original_meses ?: null,
        ]);

        return redirect()->route('deudas');
    }

    public function pay(Request $request, Debt $debt): RedirectResponse
    {
        // Authorization: only the owner may pay their own debt
        if ($debt->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->debtService->applyPayment($debt, (float) $validated['amount']);

        return redirect()->back()->with('success', 'Disparo certero. Saldo actualizado.');
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        if ($debt->user_id !== auth()->id()) {
            abort(403);
        }

        $debt->delete();

        return redirect()->back();
    }
}