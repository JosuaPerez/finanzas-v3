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
        // Decodificamos los detalles del presupuesto
        $details = is_string($this->budget->details) ? json_decode($this->budget->details, true) : $this->budget->details;

        // 1. Fila de Ingreso
        $rows[] = ['💰 INGRESO', 'Quincena Total', $this->budget->income];
        $rows[] = ['', '', '']; // Espacio en blanco

        // 2. Filas de Gastos Fijos
        foreach ($details['fixed'] ?? [] as $fixed) {
            $rows[] = ['📉 Gasto Fijo', $fixed['name'], $fixed['amount']];
        }
        $rows[] = ['', '', '']; // Espacio en blanco

        // 3. Filas de Distribución (Si hay)
        if (!empty($details['distribution'])) {
            foreach ($details['distribution'] as $dist) {
                $rows[] = ['🎯 Distribución', $dist['name'], $dist['amount']];
            }
            $rows[] = ['', '', '']; // Espacio en blanco
        }

        // 4. Fila Final (Capital Libre)
        $rows[] = ['⚖️ RESUMEN', 'Capital Libre Restante', $details['remaining'] ?? 0];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Categoría',
            'Concepto',
            'Monto (RD$)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la primera fila (Encabezados): Negrita, texto blanco, fondo azul oscuro
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A8A']]
            ],
        ];
    }
}