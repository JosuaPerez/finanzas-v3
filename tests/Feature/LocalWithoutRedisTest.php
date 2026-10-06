<?php

use App\Models\Expense;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\File;

it('records expenses without Redis and still limits repeated requests', function () {
    $cachePath = sys_get_temp_dir().'/finanzas-cache-'.bin2hex(random_bytes(8));
    config([
        'cache.default' => 'file',
        'cache.stores.file.path' => $cachePath,
        'cache.stores.file.lock_path' => $cachePath,
        'queue.default' => 'sync',
    ]);
    app()->forgetInstance(RateLimiter::class);
    $user = User::factory()->create(['preferred_currency' => 'MXN']);

    try {
        $this->actingAs($user);
        for ($i = 0; $i < 30; $i++) {
            $this->post(route('quick-attack.store'), [
                'monto' => 25.50, 'descripcion' => 'Transporte', 'currency' => 'MXN',
            ])->assertRedirect()->assertSessionHasNoErrors();
        }

        $this->post(route('quick-attack.store'), [
            'monto' => 25.50, 'descripcion' => 'No guardar', 'currency' => 'MXN',
        ])->assertStatus(429);

        expect(Expense::where('user_id', $user->id)->count())->toBe(30);
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id, 'amount' => 25.50, 'currency' => 'MXN',
        ]);
        $this->assertDatabaseMissing('expenses', ['description' => 'No guardar']);
    } finally {
        File::deleteDirectory($cachePath);
    }
});
