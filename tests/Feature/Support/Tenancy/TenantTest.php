<?php

use App\Models\Family;
use App\Models\House;
use App\Support\Tenancy\Tenant;

test('running as a family restores the previous family afterwards, even when the callback throws', function () {
    $tenant = app(Tenant::class);
    [$outer, $inner] = Family::factory()->count(2)->create();
    $tenant->set($outer);

    expect(fn () => $tenant->for($inner, fn () => throw new RuntimeException))
        ->toThrow(RuntimeException::class);

    expect($tenant->family()->is($outer))->toBeTrue();
});

test('the current house skips archived houses', function () {
    $family = Family::factory()->create();
    House::factory()->for($family)->archived()->create();
    $activeHouse = House::factory()->for($family)->create();

    $currentHouse = app(Tenant::class)->for($family, fn () => app(Tenant::class)->house());

    expect($currentHouse->is($activeHouse))->toBeTrue();
});
