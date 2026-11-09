<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->category = asFamily($this->family, fn () => $this->family->categories()->firstOrFail());
});

test('guests are redirected to the login page', function () {
    $this->get(route('finances'))->assertRedirect(route('login'));
});

test('finances shows the current month\'s expenses and what was spent', function () {
    $this->travelTo('2026-11-15');
    $inMonth = Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $this->category->id, 'amount_cents' => 1999, 'paid_on' => '2026-11-01']);
    Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $this->category->id, 'amount_cents' => 500, 'paid_on' => '2026-11-30']);
    Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $this->category->id, 'amount_cents' => 9999, 'paid_on' => '2026-10-31']);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Finances')
            ->where('month', '2026-11')
            ->has('expenses', 2)
            ->where('expenses.1.id', $inMonth->id)
            ->where('expenses.1.paid_on', '2026-11-01')
            ->where('spentCents', 2499)
            ->has('categories', 9));
});

test('another month can be chosen', function () {
    Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $this->category->id, 'amount_cents' => 9999, 'paid_on' => '2026-10-31']);

    $this->actingAs($this->user)
        ->get(route('finances', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('month', '2026-10')
            ->has('expenses', 1)
            ->where('spentCents', 9999));
});

test('an invalid month is rejected', function () {
    $this->actingAs($this->user)
        ->get(route('finances', ['month' => 'October']))
        ->assertSessionHasErrors('month');
});

test('another family\'s expenses and categories never show', function () {
    $this->travelTo('2026-11-15');
    Expense::factory()->create(['amount_cents' => 4200, 'paid_on' => '2026-11-10']);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses', 0)
            ->where('spentCents', 0)
            ->has('categories', 9));
});

test('an expense in an archived category still shows that category', function () {
    $this->travelTo('2026-11-15');
    $archived = Category::factory()->archived()->create(['family_id' => $this->family->id, 'name' => 'Gym', 'position' => 20]);
    Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $archived->id, 'paid_on' => '2026-11-10']);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.category_id', $archived->id)
            ->where('categories.9.id', $archived->id)
            ->where('categories.9.archived', true));
});

test('finances lists the family\'s payment methods and which one paid each expense', function () {
    $this->travelTo('2026-11-15');
    $method = PaymentMethod::factory()->create(['family_id' => $this->family->id, 'name' => 'TD Visa']);
    PaymentMethod::factory()->create(['name' => 'Not ours']);
    Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $this->category->id, 'payment_method_id' => $method->id, 'paid_on' => '2026-11-10']);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('paymentMethods', 1)
            ->where('paymentMethods.0.name', 'TD Visa')
            ->where('expenses.0.payment_method_id', $method->id));
});
