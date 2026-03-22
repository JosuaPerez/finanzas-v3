<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Debt;

class DebtController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'minimum_payment' => 'required|numeric|min:0',
        ]);

        Debt::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'balance' => $request->balance,
            'interest_rate' => $request->interest_rate ?? 0,
            'minimum_payment' => $request->minimum_payment ?? 0,
            'type' => $request->type ?? 'loan',
            'credit_limit' => $request->credit_limit,
            'cutoff_date' => $request->cutoff_date,
            'payment_date' => $request->payment_date,
            'original_amount' => $request->original_amount,
            'currency' => $request->currency ?? 'DOP',
            'overdraft_percentage' => $request->overdraft_percentage ?? 0,
        ]);

        return redirect()->route('deudas');
    }

    // Función para Abonar al saldo
    public function pay(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        $amount = $request->amount;
        
        // 1. Descontamos el saldo de la deuda
        $debt = \App\Models\Debt::where('user_id', auth()->id())->findOrFail($id);
        $debt->balance -= $amount;
        if ($debt->balance < 0) {
            $debt->balance = 0; 
        }
        $debt->save();

        // 2. Buscamos el presupuesto para restar las municiones Y GUARDAR EL RECIBO
        $budget = \App\Models\Budget::where('user_id', auth()->id())->latest()->first();
        
        if ($budget) {
            $details = is_string($budget->details) ? json_decode($budget->details, true) : $budget->details;
            
            // Restamos del capital libre
            $currentRemaining = $details['remaining'] ?? 0;
            $details['remaining'] = max(0, $currentRemaining - $amount);
            
            // 🛠️ LA MAGIA: Guardamos el recibo del pago
            if (!isset($details['debt_payments'])) {
                $details['debt_payments'] = [];
            }
            $details['debt_payments'][] = [
                'name' => $debt->name,
                'amount' => $amount,
            ];
            
            $budget->details = $details;
            $budget->save();
        }

        return redirect()->back()->with('success', 'Disparo certero. Saldo actualizado.');
    }

    // Función para Eliminar una deuda
    public function destroy(Debt $debt)
    {
        if ($debt->user_id !== auth()->id()) {
            abort(403);
        }

        $debt->delete();
        return redirect()->back();
    }
}