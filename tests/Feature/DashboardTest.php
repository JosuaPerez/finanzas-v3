<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    // Esto asegura que la base de datos se limpie después de cada prueba
    use RefreshDatabase;

    public function test_el_dashboard_calcula_y_envia_las_deudas_activas_correctamente(): void
    {
        // 1. Crear un soldado (usuario) de prueba
        $user = User::factory()->create();

        // 2. Asignarle deudas (2 activas y 1 pagada)
        Debt::create([
            'user_id' => $user->id,
            'name' => 'Préstamo Táctico',
            'balance' => 5000,
        ]);

        Debt::create([
            'user_id' => $user->id,
            'name' => 'Tarjeta de Suministros',
            'balance' => 3000,
        ]);

        Debt::create([
            'user_id' => $user->id,
            'name' => 'Deuda Antigua Pagada',
            'balance' => 0, // Esta NO debe sumarse
        ]);

        // 3. Simular que el soldado inicia sesión y entra al Dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        // 4. VERIFICACIÓN: El servidor debe responder 200 (OK)
        $response->assertStatus(200);

        // 5. VERIFICACIÓN: Inertia (Vue) debe recibir exactamente 8000 en la variable 'totalDebts'
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('totalDebts')
            ->where('totalDebts', 8000)
        );
    }
}