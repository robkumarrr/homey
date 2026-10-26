<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('house page shows the member\'s current house', function () {
    $user = User::factory()->create();
    $family = $user->currentFamily();
    $house = asFamily($family, fn () => $family->houses()->sole());

    $this->actingAs($user)
        ->get(route('house.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/House')
            ->where('house.id', $house->id)
            ->where('house.name', 'Home'));
});

test('house name and address can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('house.update'), [
            'name' => 'The Maple House',
            'address' => '123 Maple Street, Toronto',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('house.edit'));

    $family = $user->currentFamily();
    $house = asFamily($family, fn () => $family->houses()->sole());

    expect($house->name)->toBe('The Maple House')
        ->and($house->address)->toBe('123 Maple Street, Toronto');
});

test('house name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('house.update'), ['name' => '', 'address' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);
});

test('updating a house leaves other families\' houses untouched', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->patch(route('house.update'), ['name' => 'Ours']);

    $otherFamily = $otherUser->currentFamily();
    $otherHouse = asFamily($otherFamily, fn () => $otherFamily->houses()->sole());

    expect($otherHouse->name)->toBe('Home');
});

test('guests are redirected to the login page from house settings', function () {
    $this->get(route('house.edit'))->assertRedirect(route('login'));
});
