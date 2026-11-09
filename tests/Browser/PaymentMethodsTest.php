<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a payment method added in settings can record what paid for an expense', function () {
    visit(route('finances'))
        ->click('@add-button')
        ->assertSee('Add your cards and accounts in Settings')
        ->navigate(route('payment-methods.index'))
        ->assertSee('No payment methods yet')
        ->click('@add-payment-method')
        ->click('@kind-credit_card')
        ->fill('name', 'TD Visa')
        ->fill('last_four', '1234')
        ->click('@save-payment-method')
        ->assertSee('Added "TD Visa".')
        ->assertSee('Credit card ··1234')
        ->navigate(route('finances'))
        ->click('@add-button')
        ->fill('name', 'Loblaws')
        ->fill('amount', '42.50')
        ->select('payment_method_id', 'TD Visa')
        ->click('@save-expense')
        ->assertSee('Added "Loblaws".')
        ->assertSee('TD Visa')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('payment_methods', ['name' => 'TD Visa', 'kind' => 'credit_card', 'last_four' => '1234']);
});

test('the payment methods page fits on a phone', function () {
    visit(route('payment-methods.index'))->on()->mobile()->assertNoSmoke();
});
