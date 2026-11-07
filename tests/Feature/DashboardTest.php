<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('home shows the member\'s own house, not another family\'s', function () {
    $user = User::factory()->create();
    $family = $user->currentFamily();
    asFamily($family, fn () => $family->houses()->update(['name' => 'The Maple House']));
    $otherFamily = User::factory()->create()->currentFamily();
    asFamily($otherFamily, fn () => $otherFamily->houses()->update(['name' => 'Not ours']));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('house.name', 'The Maple House'));
});
