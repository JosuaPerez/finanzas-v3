<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba 1: Crear un nuevo enemigo en el radar
     */
    public function test_usuario_puede_registrar_una_deuda(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'name' => 'Tarjeta del Banco',
            'balance' => 50000,
            'interest_rate' => 60,
            'minimum_payment' => 2000
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('debts', [
            'user_id' => $user->id,
            'name' => 'Tarjeta del Banco',
            'balance' => 50000
        ]);
    }

    /**
     * Prueba 2: Atacar al enemigo (El saldo debe disminuir)
     */
    public function test_usuario_puede_atacar_una_deuda_y_disminuir_saldo(): void
    {
        $user = User::factory()->create();

        // 1. Inyectamos una deuda directamente en la base de datos de prueba
        $debt = Debt::create([
            'user_id' => $user->id,
            'name' => 'Préstamo',
            'balance' => 10000,
            'interest_rate' => 15,
            'minimum_payment' => 500
        ]);

        // 2. Simulamos el disparo (abono de 3,000)
        $this->actingAs($user)->post(route('debts.pay', $debt->id), [
            'amount' => 3000
        ]);

        // 3. Verificamos que la base de datos ahora diga que debe 7,000
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'balance' => 7000
        ]);
    }

    /**
     * Prueba 3: Eliminar a un enemigo por completo
     */
    public function test_usuario_puede_eliminar_una_deuda(): void
    {
        $user = User::factory()->create();

        $debt = Debt::create([
            'user_id' => $user->id,
            'name' => 'Deuda a Borrar',
            'balance' => 5000,
            'interest_rate' => 0,
            'minimum_payment' => 0
        ]);

        // Simulamos dar clic en el botón rojo de eliminar
        $this->actingAs($user)->delete(route('debts.destroy', $debt->id));

        // Verificamos que ese registro ya NO exista en la base de datos
        $this->assertDatabaseMissing('debts', [
            'id' => $debt->id,
        ]);
    }
}