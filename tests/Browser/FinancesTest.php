<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a member can add an expense and then edit it', function () {
    visit(route('finances'))
        ->assertSee('Nothing spent this month yet')
        ->click('@add-button')
        ->fill('name', 'Loblaws')
        ->fill('amount', '$1,200')
        ->select('category_id', 'Groceries')
        ->click('@save-expense')
        ->assertSee('Loblaws')
        ->assertSee('$1,200.00')
        ->assertSee('Expenses · 1')
        ->click('@expense-actions')
        ->click('Edit')
        ->assertValue('name', 'Loblaws')
        ->fill('name', 'Costco')
        ->fill('amount', '42.50')
        ->click('@save-expense')
        ->assertSee('Costco')
        ->assertSee('$42.50')
        ->assertDontSee('Loblaws')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseCount('expenses', 1);
    $this->assertDatabaseHas('expenses', ['name' => 'Costco', 'amount_cents' => 4250]);
});

test('mistakes are shown on the field that needs fixing', function () {
    visit(route('finances'))
        ->click('@add-button')
        ->fill('name', 'Loblaws')
        ->fill('amount', 'abc')
        ->click('@save-expense')
        ->assertSee('Enter an amount like 19.99 or $1,200.')
        ->assertSee('Nothing spent this month yet')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseCount('expenses', 0);
});

test('every tab loads without errors, on desktop and on a phone', function () {
    $tabs = ['dashboard', 'finances', 'tasks', 'projects', 'pets'];

    visit(array_map(fn (string $tab) => route($tab), $tabs))->assertNoSmoke();
    visit(array_map(fn (string $tab) => route($tab), $tabs))->on()->mobile()->assertNoSmoke();
});
