<?php

use App\Models\Family;
use App\Models\House;
use App\Support\Tenancy\Tenant;
use App\Support\Tenancy\TenantNotResolved;

test('family-owned records are limited to the resolved family', function () {
    $family = Family::factory()->create();
    $ownHouse = House::factory()->for($family)->create();
    House::factory()->create();

    $houseIds = app(Tenant::class)->for($family, fn () => House::pluck('id')->all());

    expect($houseIds)->toBe([$ownHouse->id]);
});

test('new records are assigned to the resolved family', function () {
    $family = Family::factory()->create();

    $house = app(Tenant::class)->for($family, fn () => House::create(['name' => 'Cottage']));

    expect($house->family_id)->toBe($family->id);
});

test('an explicitly set family is kept on create', function () {
    [$resolvedFamily, $otherFamily] = Family::factory()->count(2)->create();

    $house = app(Tenant::class)->for($resolvedFamily, fn () => $otherFamily->houses()->create(['name' => 'Cottage']));

    expect($house->family_id)->toBe($otherFamily->id);
});

test('reading family-owned records throws when no family is resolved', function () {
    House::factory()->create();

    expect(fn () => House::count())->toThrow(TenantNotResolved::class);
});

test('creating a family-owned record without a family throws when none is resolved', function () {
    expect(fn () => House::create(['name' => 'Cottage']))->toThrow(TenantNotResolved::class);
});
