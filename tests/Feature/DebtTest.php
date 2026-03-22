<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Debt;
use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba 1: Registrar una Tarjeta de Crédito (Con Límite, Moneda y Fechas)
     */
    public function test_usuario_puede_registrar_tarjeta_de_credito(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'type' => 'credit_card',
            'currency' => 'USD',
            'name' => 'Visa Dólares',
            'balance' => 500,
            'interest_rate' => 60,
            'minimum_payment' => 50,
            'credit_limit' => 1000,
            'cutoff_date' => 15,
            'payment_date' => 5,
            'overdraft_percentage' => 10
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('debts', [
            'user_id' => $user->id,
            'name' => 'Visa Dólares',
            'type' => 'credit_card',
            'currency' => 'USD',
            'credit_limit' => 1000
        ]);
    }

    /**
     * Prueba 2: Registrar un Préstamo (Con Monto Original)
     */
    public function test_usuario_puede_registrar_prestamo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'type' => 'loan',
            'currency' => 'DOP',
            'name' => 'Préstamo Vehículo',
            'balance' => 800000,
            'interest_rate' => 14,
            'minimum_payment' => 15000,
            'original_amount' => 1000000
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('debts', [
            'name' => 'Préstamo Vehículo',
            'type' => 'loan',
            'original_amount' => 1000000
        ]);
    }

    /**
     * Prueba 3: Atacar una deuda disminuye el saldo Y guarda el recibo en el presupuesto
     */
    public function test_pago_de_deuda_se_registra_en_presupuesto(): void
    {
        $user = User::factory()->create();

        // 1. Creamos un Presupuesto en la Trinchera con RD$ 30,000 de capital libre
        $budget = Budget::create([
            'user_id' => $user->id,
            'title' => 'Quincena Test',
            'income' => 50000,
            'fixed_expenses_total' => 20000,
            'details' => json_encode(['remaining' => 30000]) 
        ]);

        // 2. Creamos un Enemigo (Deuda) de RD$ 10,000
        $debt = Debt::create([
            'user_id' => $user->id,
            'name' => 'Tarjeta Test',
            'balance' => 10000,
        ]);

        // 3. ¡FUEGO! Simulamos un disparo de RD$ 5,000
        $this->actingAs($user)->post(route('debts.pay', $debt->id), [
            'amount' => 5000
        ]);

        // 4. Verificamos que la deuda bajó a RD$ 5,000
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'balance' => 5000
        ]);

        // 5. Verificamos que el presupuesto guardó el recibo y bajó el capital libre a RD$ 25,000
        $budget->refresh();
        $details = is_string($budget->details) ? json_decode($budget->details, true) : $budget->details;
        
        $this->assertEquals(25000, $details['remaining']); // Capital libre actualizado
        $this->assertCount(1, $details['debt_payments']); // Hay 1 recibo de pago
        $this->assertEquals('Tarjeta Test', $details['debt_payments'][0]['name']); // Se pagó a la tarjeta correcta
        $this->assertEquals(5000, $details['debt_payments'][0]['amount']); // Por el monto exacto
    }
}