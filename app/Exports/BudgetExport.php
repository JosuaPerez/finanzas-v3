<?php

namespace App\Exports;

use App\Models\Budget;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $budget;

    public function __construct(Budget $budget)
    {
        $this->budget = $budget;
    }

    public function array(): array
    {
        $rows = [];
        $details = is_string($this->budget->details) ? json_decode($this->budget->details, true) : $this->budget->details;

        // 1. Ingresos
        $rows[] = ['💰 INGRESO', 'Quincena Total', $this->budget->income];
        $rows[] = ['', '', '']; 

        // 2. Gastos Fijos
        foreach ($details['fixed'] ?? [] as $fixed) {
            $rows[] = ['📉 Gasto Fijo', $fixed['name'], $fixed['amount']];
        }
        $rows[] = ['', '', ''];

        // 3. ⚔️ NUEVA SECCIÓN: Pagos a Deudas
        if (!empty($details['debt_payments'])) {
            foreach ($details['debt_payments'] as $payment) {
                $rows[] = ['⚔️ Ataque a Deuda', $payment['name'], $payment['amount']];
            }
            $rows[] = ['', '', ''];
        }

        // 4. Resumen Final
        $rows[] = ['⚖️ RESUMEN', 'Capital Libre Restante', $details['remaining'] ?? 0];

        return $rows;
    }

    public function headings(): array
    {
        return ['Categoría', 'Concepto', 'Monto (RD$)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A8A']]
            ],
        ];
    }
}