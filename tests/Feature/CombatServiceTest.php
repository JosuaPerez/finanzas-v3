<?php

use App\Models\CampaignBoss;
use App\Models\User;
use App\Services\CombatService;

it('damages an active campaign boss without defeating it when damage is less than health', function () {
    $user = User::factory()->create(['current_xp' => 0, 'level' => 1]);
    
    $boss = CampaignBoss::create([
        'user_id' => $user->id,
        'name' => 'Jefe de Prueba',
        'max_health' => 100,
        'current_health' => 100,
        'experience_reward' => 50,
        'order' => 1,
        'is_defeated' => false,
    ]);

    $service = new CombatService();
    $result = $service->processAttack($user, 40);

    expect($result)->toBeFalse();
    
    $boss->refresh();
    expect($boss->current_health)->toBe(60)
        ->and($boss->is_defeated)->toBeFalse();
        
    $user->refresh();
    expect($user->current_xp)->toBe(0);
});

it('defeats a boss, clamps HP to zero, awards XP, and levels up the user on lethal damage', function () {
    $user = User::factory()->create(['current_xp' => 50, 'level' => 1]);
    
    $boss = CampaignBoss::create([
        'user_id' => $user->id,
        'name' => 'Jefe Letal',
        'max_health' => 100,
        'current_health' => 30,
        'experience_reward' => 100, // 50 + 100 = 150 XP -> L2 threshold is 100
        'order' => 1,
        'is_defeated' => false,
    ]);

    $service = new CombatService();
    $result = $service->processAttack($user, 50); // 50 > 30

    expect($result)->toBeTrue();
    
    $boss->refresh();
    expect($boss->current_health)->toBe(0)
        ->and($boss->is_defeated)->toBeTrue();
        
    $user->refresh();
    expect($user->current_xp)->toBe(150)
        ->and($user->level)->toBe(2);
});

it('returns false when a user attacks but has no active campaign bosses', function () {
    $user = User::factory()->create();
    
    // Create only a defeated boss
    CampaignBoss::create([
        'user_id' => $user->id,
        'name' => 'Jefe Derrotado',
        'max_health' => 100,
        'current_health' => 0,
        'experience_reward' => 50,
        'order' => 1,
        'is_defeated' => true,
    ]);

    $service = new CombatService();
    $result = $service->processAttack($user, 50);

    expect($result)->toBeFalse();
});

it('selects the boss with the lowest order when multiple active bosses exist', function () {
    $user = User::factory()->create();
    
    $boss2 = CampaignBoss::create([
        'user_id' => $user->id,
        'name' => 'Segundo Jefe',
        'max_health' => 100,
        'current_health' => 100,
        'experience_reward' => 50,
        'order' => 2,
        'is_defeated' => false,
    ]);

    $boss1 = CampaignBoss::create([
        'user_id' => $user->id,
        'name' => 'Primer Jefe',
        'max_health' => 100,
        'current_health' => 100,
        'experience_reward' => 50,
        'order' => 1,
        'is_defeated' => false,
    ]);

    $service = new CombatService();
    $service->processAttack($user, 30);

    $boss1->refresh();
    $boss2->refresh();

    expect($boss1->current_health)->toBe(70)
        ->and($boss2->current_health)->toBe(100);
});

it('defines the correct eloquent relationships between User and CampaignBoss', function () {
    $user = User::factory()->create();
    $boss = CampaignBoss::create([
        'user_id' => $user->id,
        'name' => 'Jefe Relación',
        'max_health' => 100,
        'current_health' => 100,
        'experience_reward' => 50,
        'order' => 1,
        'is_defeated' => false,
    ]);

    expect($boss->user->id)->toBe($user->id)
        ->and($user->campaignBosses->first()->id)->toBe($boss->id);
});
