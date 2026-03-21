<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetTest extends TestCase
{
    use RefreshDatabase; // Siempre limpia la base de datos antes de cada prueba

    /**
     * Prueba 1: El soldado puede ver su centro de mando (Dashboard)
     */
    public function test_usuario_puede_ver_el_dashboard_de_presupuesto(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Prueba 2: El soldado puede guardar su presupuesto y se registra en la BD
     */
    public function test_usuario_puede_guardar_un_presupuesto(): void
    {
        $user = User::factory()->create();

        // Simulamos que el usuario envía el formulario de guardar presupuesto
        $response = $this->actingAs($user)->post(route('budgets.store'), [
            'title' => 'Quincena de Prueba',
            'income' => 35000,
            'fixed_expenses_total' => 15000,
            'details' => [
                'fixed' => [['name' => 'Casa', 'amount' => 15000]],
                'remaining' => 20000
            ]
        ]);

        // Verificamos que no haya errores de validación
        $response->assertSessionHasNoErrors();

        // Afirmamos (Assert) que la base de datos ahora tiene este registro
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'income' => 35000,
            'fixed_expenses_total' => 15000,
        ]);
    }
}