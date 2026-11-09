<?php

use App\Models\Commitment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->actingAs($this->user);
});

test('a member can add a bill and see it due this month', function () {
    visit(route('finances'))
        ->assertSee('No bills due this month.')
        ->click('@add-bill-button')
        ->assertSee('Add bill')
        ->fill('bill-name', 'Hydro')
        ->fill('bill-amount', '120')
        ->click('#bill-amount-varies')
        ->select('bill-repeats', 'Every 2 weeks')
        ->fill('bill-starts-on', now()->startOfMonth()->toDateString())
        ->click('@save-bill')
        ->assertSee('Added "Hydro".')
        ->assertSee('$120.00')
        ->assertSee('Enter amount')
        ->assertDontSee('No bills due this month.')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('commitments', ['name' => 'Hydro', 'amount_varies' => true, 'frequency' => 'weekly', 'interval' => 2]);
    $this->assertDatabaseHas('commitment_exceptions', ['amount_cents' => 12000]);
});

test('the add dialog switches between an expense and a bill', function () {
    visit(route('finances'))
        ->click('@add-button')
        ->assertSee('Add expense')
        ->click('@mode-bill')
        ->assertSee('Add bill')
        ->fill('bill-name', 'Netflix')
        ->fill('bill-amount', '20.99')
        ->click('@mode-expense')
        ->assertSee('Add expense')
        ->click('@mode-bill')
        ->assertValue('bill-name', 'Netflix')
        ->click('@save-bill')
        ->assertSee('Added "Netflix".')
        ->assertNoJavaScriptErrors();
});

test('a bill can be edited, undone, archived and unarchived from All bills', function () {
    $bill = Commitment::factory()->costing(2099)->create([
        'family_id' => $this->family->id,
        'category_id' => asFamily($this->family, fn () => $this->family->categories()->firstOrFail())->id,
        'name' => 'Netflix',
        'starts_on' => now()->startOfMonth()->toDateString(),
    ]);

    visit(route('finances'))
        ->click('@bill-actions')
        ->click('Edit bill')
        ->assertValue('bill-name', 'Netflix')
        ->fill('bill-name', 'Netflix Premium')
        ->click('@save-bill')
        ->assertSee('Saved "Netflix Premium".')
        ->click('Undo')
        ->assertSee('Saved "Netflix".')
        ->click('@all-bills')
        ->assertSee('Monthly on the 1st')
        ->click("@all-bills-actions-{$bill->id}")
        ->click('Archive bill')
        ->assertSee('Archived "Netflix".')
        ->assertSee('Archived · 1')
        ->click("@unarchive-bill-{$bill->id}")
        ->assertSee('Unarchived "Netflix".')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('commitments', ['id' => $bill->id, 'name' => 'Netflix', 'archived_at' => null]);
});

test('a month\'s amount can be entered, and a fixed bill\'s changed from a month on', function () {
    $this->travelTo(now()->startOfMonth()->addDays(9));
    $category = asFamily($this->family, fn () => $this->family->categories()->firstOrFail())->id;
    Commitment::factory()->create(['family_id' => $this->family->id, 'category_id' => $category, 'name' => 'Hydro', 'amount_varies' => true, 'starts_on' => now()->subMonth()->toDateString()]);
    Commitment::factory()->costing(185000)->create(['family_id' => $this->family->id, 'category_id' => $category, 'name' => 'Mortgage', 'starts_on' => now()->subMonths(3)->addDays(5)->toDateString()]);

    visit(route('finances'))
        ->click('@enter-amount')
        ->assertSee('Enter amount')
        ->assertDontSee('This and later months')
        ->fill('occurrence-amount', '134.20')
        ->click('@save-amount')
        ->assertSee('$134.20')
        ->assertDontSee('Enter amount')
        ->click('[aria-label="More for Mortgage"]')
        ->click('Change amount…')
        ->click('@scope-onward')
        ->fill('occurrence-amount', '1910')
        ->click('@save-amount')
        ->assertSee('$1,910.00')
        ->click('Undo')
        ->assertSee('$1,850.00')
        ->assertNoJavaScriptErrors();
});
