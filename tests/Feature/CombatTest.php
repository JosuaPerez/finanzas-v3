<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CombatTest extends TestCase
{
    // Refresca la base de datos de pruebas para que esté limpia
    use RefreshDatabase; 

    /**
     * Prueba 1: Verificar la redirección de la puerta principal.
     */
    public function test_la_raiz_redirige_al_login(): void
    {
        // Simulamos que un usuario entra a tu-pagina.com/
        $response = $this->get('/');

        // Afirmamos (Assert) que la aplicación lo redirigió al /login
        $response->assertRedirect('/login');
    }

    /**
     * Prueba 2: Verificar la seguridad del cuartel (Middleware).
     */
    public function test_los_civiles_no_pueden_ver_el_historial(): void
    {
        // Simulamos que alguien sin cuenta intenta entrar directo al historial
        $response = $this->get('/historial');

        // Afirmamos que el sistema lo patea de vuelta al login
        $response->assertRedirect('/login');
    }

    /**
     * Prueba 3: Verificar que un soldado con acceso sí puede entrar.
     */
    public function test_los_soldados_pueden_ver_el_historial(): void
    {
        // 1. Creamos un usuario de prueba (soldado falso)
        $user = User::factory()->create();

        // 2. Simulamos que ese usuario inicia sesión y visita el historial
        $response = $this->actingAs($user)->get('/historial');

        // 3. Afirmamos que la página carga con éxito (Código HTTP 200 OK)
        $response->assertStatus(200);
    }
}