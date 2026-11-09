<?php

use App\Models\Category;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->actingAs($this->user);
});

test('a category added in settings can be picked for an expense', function () {
    visit(route('finances'))
        ->click('@categories-link')
        ->assertPathIs('/settings/categories')
        ->click('@add-category')
        ->fill('name', 'Kids')
        ->click('@icon-baby')
        ->click('@colour-rose')
        ->click('@save-category')
        ->assertSee('Added "Kids".')
        ->navigate(route('finances'))
        ->click('@add-button')
        ->fill('name', 'Diapers')
        ->fill('amount', '40')
        ->select('category_id', 'Kids')
        ->click('@save-expense')
        ->assertSee('Added "Diapers".')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('categories', ['name' => 'Kids', 'icon' => 'baby', 'colour' => 'rose']);
});

test('a category can be archived, undone, and unarchived', function () {
    $dining = asFamily($this->family, fn () => Category::where('name', 'Dining')->sole());

    visit(route('categories.index'))
        ->click("@category-actions-{$dining->id}")
        ->click('Archive')
        ->assertSee('Archived "Dining".')
        ->assertSee('Archived · 1')
        ->click('Undo')
        ->assertSee('Unarchived "Dining".')
        ->assertDontSee('Archived · 1')
        ->click("@category-actions-{$dining->id}")
        ->click('Archive')
        ->click("@unarchive-{$dining->id}")
        ->assertSee('Unarchived "Dining".')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('categories', ['name' => 'Dining', 'archived_at' => null]);
});

test('the categories page fits on a phone', function () {
    visit(route('categories.index'))->on()->mobile()->assertNoSmoke();
});
