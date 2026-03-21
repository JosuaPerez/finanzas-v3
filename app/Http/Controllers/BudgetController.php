<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Budget;
use App\Exports\BudgetExport;
use Maatwebsite\Excel\Facades\Excel;

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

    // Envía los datos a la nueva página de Historial
    public function history()
    {
        $misPresupuestos = \App\Models\Budget::where('user_id', auth()->id())->latest()->get();
        return inertia('Historial', ['budgets' => $misPresupuestos]);
    }

    // Ahora acepta un ID para descargar cualquier Excel
    public function export($id = null)
    {
        if ($id) {
            // Si le pasamos un ID, busca ese presupuesto específico
            $budget = \App\Models\Budget::where('user_id', auth()->id())->findOrFail($id);
        } else {
            // Si no le pasamos ID (desde el Dashboard), busca el último
            $budget = \App\Models\Budget::where('user_id', auth()->id())->latest()->first();
        }

        if (!$budget) {
            return redirect()->back()->with('error', 'No hay datos para exportar.');
        }

        // Creamos un nombre de archivo dinámico con la fecha de esa batalla
        $nombreArchivo = 'Reporte_Batalla_' . date('Y-m-d', strtotime($budget->created_at)) . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\BudgetExport($budget), $nombreArchivo);
    }
}
