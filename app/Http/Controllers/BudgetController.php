<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Budget;

class BudgetController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar que la información esté correcta
        $request->validate([
            'title' => 'required|string|max:255',
            'income' => 'required|numeric|min:0',
            'fixed_expenses_total' => 'required|numeric|min:0',
            'details' => 'required|array',
        ]);

        // 2. Guardar en la base de datos amarrado al usuario que inició sesión
        Budget::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'income' => $request->income,
            'fixed_expenses_total' => $request->fixed_expenses_total,
            'details' => json_encode($request->details), // Convertimos el array a JSON
        ]);

        // 3. Devolverlo a la página
        return redirect()->route('dashboard');
    }
}
