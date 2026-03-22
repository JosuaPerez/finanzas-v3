<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba: Verificar que el sistema envía nuestro correo táctico de recuperación
     */
    public function test_sistema_envia_correo_tactico_de_recuperacion(): void
    {
        // 1. Activamos el modo espía para interceptar notificaciones
        Notification::fake();

        // 2. Creamos un soldado de prueba
        $user = User::factory()->create();

        // 3. Simulamos que el soldado pide recuperar su contraseña
        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        // 4. Afirmamos que se le envió NUESTRA notificación personalizada, no la de Laravel
        Notification::assertSentTo(
            [$user], CustomResetPassword::class
        );
    }
}