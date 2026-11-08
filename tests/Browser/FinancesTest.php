<?php

use App\Models\DeletedExpense;
use App\Models\Expense;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->actingAs($this->user);
});

function anExpense(string $name): Expense
{
    $family = test()->family;

    return Expense::factory()->create([
        'family_id' => $family->id,
        'category_id' => asFamily($family, fn () => $family->categories()->firstOrFail())->id,
        'name' => $name,
        'amount_cents' => 14237,
        'paid_on' => now()->toDateString(),
    ]);
}

test('a member can add an expense and then edit it', function () {
    visit(route('finances'))
        ->assertSee('Nothing spent this month yet')
        ->click('@add-button')
        ->fill('name', 'Loblaws')
        ->fill('amount', '$1,200')
        ->select('category_id', 'Groceries')
        ->click('@save-expense')
        ->assertSee('Added "Loblaws".')
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

test('deleting asks first, and the toast can undo it', function () {
    anExpense('Loblaws');

    visit(route('finances'))
        ->click('@expense-actions')
        ->click('Delete…')
        ->assertSee('Delete this expense?')
        ->click('Cancel')
        ->assertSee('Expenses · 1')
        ->click('@expense-actions')
        ->click('Delete…')
        ->click('@confirm-delete')
        ->assertSee('Deleted "Loblaws".')
        ->assertSee('Expenses · 0')
        ->click('Undo')
        ->assertSee('Restored "Loblaws".')
        ->assertSee('Expenses · 1')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('expenses', ['name' => 'Loblaws']);
    $this->assertDatabaseCount('deleted_expenses', 0);
});

test('a deleted expense can be restored from Recently deleted', function () {
    anExpense('Costco');

    $page = visit(route('finances'))
        ->click('@expense-actions')
        ->click('Delete…')
        ->click('@confirm-delete')
        ->assertSee('Expenses · 0');

    $deletedId = asFamily($this->family, fn () => DeletedExpense::sole()->id);

    $page->click('@recently-deleted')
        ->assertSee('Costco')
        ->click("@restore-{$deletedId}")
        ->assertSee('Restored "Costco".')
        ->assertSee('Expenses · 1')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseCount('deleted_expenses', 0);
});

test('an edit can be undone from its toast', function () {
    anExpense('Loblaws');

    visit(route('finances'))
        ->click('@expense-actions')
        ->click('Edit')
        ->fill('name', 'Costco')
        ->click('@save-expense')
        ->assertSee('Saved "Costco".')
        ->click('Undo')
        ->assertSee('Saved "Loblaws".')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('expenses', ['name' => 'Loblaws', 'amount_cents' => 14237]);
});

test('every tab loads without errors, on desktop and on a phone', function () {
    $tabs = ['dashboard', 'finances', 'tasks', 'projects', 'pets'];

    visit(array_map(fn (string $tab) => route($tab), $tabs))->assertNoSmoke();
    visit(array_map(fn (string $tab) => route($tab), $tabs))->on()->mobile()->assertNoSmoke();
});
