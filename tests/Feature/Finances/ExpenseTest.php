<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Scopes\FamilyScope;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->category = asFamily($this->family, fn () => $this->family->categories()->firstOrFail());
});

function expenseInput(array $overrides = []): array
{
    return [
        'amount' => '19.99',
        'category_id' => test()->category->id,
        'paid_on' => '2026-11-07',
        'note' => 'Loblaws',
        ...$overrides,
    ];
}

test('a member can record an expense', function (string $amount, int $cents) {
    $this->actingAs($this->user)
        ->from(route('finances'))
        ->post(route('finances.expenses.store'), expenseInput(['amount' => $amount]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('finances'));

    $expense = asFamily($this->family, fn () => Expense::sole());

    expect($expense->amount_cents)->toBe($cents)
        ->and($expense->category_id)->toBe($this->category->id)
        ->and($expense->paid_on->toDateString())->toBe('2026-11-07')
        ->and($expense->note)->toBe('Loblaws')
        ->and($expense->family_id)->toBe($this->family->id)
        ->and($expense->created_by)->toBe($this->user->id);
})->with([
    ['19.99', 1999],
    ['$1,200', 120000],
    ['5', 500],
]);

test('the amount must be a plain amount above zero', function (string $amount) {
    $this->actingAs($this->user)
        ->post(route('finances.expenses.store'), expenseInput(['amount' => $amount]))
        ->assertSessionHasErrors(['amount' => 'Enter an amount like 19.99 or $1,200.']);

    expect(Expense::withoutGlobalScope(FamilyScope::class)->count())->toBe(0);
})->with(['abc', '0', '-5', '19.999']);

test('an expense needs a category and a date, and the note is optional', function () {
    $this->actingAs($this->user)
        ->post(route('finances.expenses.store'), expenseInput(['category_id' => null, 'paid_on' => 'yesterday']))
        ->assertSessionHasErrors(['category_id', 'paid_on']);

    $this->actingAs($this->user)
        ->post(route('finances.expenses.store'), expenseInput(['note' => null]))
        ->assertSessionHasNoErrors();
});

test('another family\'s category cannot be used', function () {
    $otherCategory = Category::factory()->create();

    $this->actingAs($this->user)
        ->post(route('finances.expenses.store'), expenseInput(['category_id' => $otherCategory->id]))
        ->assertSessionHasErrors('category_id');
});

test('an archived category cannot be used for a new expense', function () {
    $archived = Category::factory()->archived()->create(['family_id' => $this->family->id]);

    $this->actingAs($this->user)
        ->post(route('finances.expenses.store'), expenseInput(['category_id' => $archived->id]))
        ->assertSessionHasErrors('category_id');
});

test('a member can edit an expense', function () {
    $expense = Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $this->category->id]);
    $newCategory = asFamily($this->family, fn () => $this->family->categories()->where('id', '!=', $this->category->id)->firstOrFail());

    $this->actingAs($this->user)
        ->patch(route('finances.expenses.update', $expense), expenseInput([
            'amount' => '$42.50',
            'category_id' => $newCategory->id,
            'paid_on' => '2026-11-02',
            'note' => 'Costco',
        ]))
        ->assertSessionHasNoErrors();

    $expense->refresh();

    expect($expense->amount_cents)->toBe(4250)
        ->and($expense->category_id)->toBe($newCategory->id)
        ->and($expense->paid_on->toDateString())->toBe('2026-11-02')
        ->and($expense->note)->toBe('Costco');
});

test('editing an expense can keep its archived category', function () {
    $archived = Category::factory()->archived()->create(['family_id' => $this->family->id]);
    $expense = Expense::factory()->create(['family_id' => $this->family->id, 'category_id' => $archived->id]);

    $this->actingAs($this->user)
        ->patch(route('finances.expenses.update', $expense), expenseInput(['category_id' => $archived->id]))
        ->assertSessionHasNoErrors();
});

test('another family\'s expense cannot be edited', function () {
    $expense = Expense::factory()->create(['amount_cents' => 1000]);

    $this->actingAs($this->user)
        ->patch(route('finances.expenses.update', $expense), expenseInput())
        ->assertNotFound();

    expect($expense->refresh()->amount_cents)->toBe(1000);
});

test('guests cannot record expenses', function () {
    $this->post(route('finances.expenses.store'), expenseInput())->assertRedirect(route('login'));
});
