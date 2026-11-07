<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

dataset('tabs', [
    'finances' => ['finances', 'Finances'],
    'tasks' => ['tasks', 'Tasks'],
    'projects' => ['projects', 'Projects'],
    'pets' => ['pets', 'Pets'],
]);

test('guests are redirected to the login page from each tab', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('tabs');

test('authenticated users can open each tab', function (string $route, string $component) {
    $this->actingAs(User::factory()->create());

    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with('tabs');
