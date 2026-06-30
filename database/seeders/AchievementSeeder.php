<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'name'           => 'Primera Sangre',
                'description'    => 'Realiza tu primer pago a una deuda.',
                'icon_name'      => '🩸',
                'condition_type' => 'debt_payment_made',
                'target_value'   => 1,
            ],
            [
                'name'           => 'Suministros Asegurados',
                'description'    => 'Registra 3 gastos fijos en el campo de batalla.',
                'icon_name'      => '🎒',
                'condition_type' => 'expenses_registered',
                'target_value'   => 3,
            ],
            [
                'name'           => 'Cazador de Jefes',
                'description'    => 'Elimina una deuda completamente. Jefe derrotado.',
                'icon_name'      => '💀',
                'condition_type' => 'debt_eliminated',
                'target_value'   => 1,
            ],
        ];

        foreach ($achievements as $data) {
            Achievement::firstOrCreate(
                ['condition_type' => $data['condition_type']],
                $data
            );
        }
    }
}
