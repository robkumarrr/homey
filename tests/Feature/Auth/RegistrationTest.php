<?php

use App\Actions\CreateFamily;
use App\Enums\FamilyRole;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registering creates a family owned by the new member, with a first house', function () {
    $this->post(route('register.store'), [
        'name' => 'Rob Kumar',
        'email' => 'rob@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'rob@example.com')->sole();
    $family = $user->families()->sole();

    expect($family->name)->toBe("Rob's family")
        ->and($family->membership->role)->toBe(FamilyRole::Owner)
        ->and(asFamily($family, fn () => $family->houses()->pluck('name')->all()))->toBe(['Home']);
});

test('registration creates nothing when the family cannot be created', function () {
    $this->mock(CreateFamily::class)
        ->shouldReceive('handle')
        ->andThrow(new RuntimeException('Family creation failed'));

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('register.store'), [
        'name' => 'Rob Kumar',
        'email' => 'rob@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]))->toThrow(RuntimeException::class, 'Family creation failed');

    $this->assertDatabaseMissing('users', ['email' => 'rob@example.com']);
});
