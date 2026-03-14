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
            'interest_rate' => $request->interest_rate,
            'minimum_payment' => $request->minimum_payment,
        ]);

        return redirect()->route('deudas');
    }

    // Función para Abonar al saldo
    public function pay(Request $request, Debt $debt)
    {
        // Seguridad: Verificar que la deuda pertenece a este usuario
        if ($debt->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        // Restamos el pago al saldo actual. El "max(0, ...)" evita que el saldo quede en negativo.
        $debt->balance = max(0, $debt->balance - $request->amount);
        $debt->save();

        return redirect()->back();
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