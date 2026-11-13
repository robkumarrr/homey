<?php

use App\Models\Commitment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-11-08');
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->category = asFamily($this->family, fn () => $this->family->categories()->firstOrFail());
});

function mortgage(): Commitment
{
    return Commitment::factory()->costing(185000)->create([
        'family_id' => test()->family->id,
        'category_id' => test()->category->id,
        'name' => 'Mortgage',
        'starts_on' => '2026-01-15',
    ]);
}

function hydro(): Commitment
{
    $hydro = Commitment::factory()->create([
        'family_id' => test()->family->id,
        'category_id' => test()->category->id,
        'name' => 'Hydro',
        'amount_varies' => true,
        'starts_on' => '2026-09-22',
    ]);
    $hydro->exceptions()->create(['original_due_on' => '2026-09-22', 'amount_cents' => 12000]);

    return $hydro;
}

/**
 * @return array<string, int|null>
 */
function amountsByMonth(Commitment $bill, array $months): array
{
    return collect($months)->mapWithKeys(fn (string $month) => [
        $month => test()->actingAs(test()->user)
            ->get(route('finances', ['month' => $month]))
            ->inertiaProps('occurrences.0.amount_cents'),
    ])->all();
}

test('changing one month\'s amount leaves every other month alone', function () {
    $bill = mortgage();

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2026-12-15', 'month_amount' => '1,900'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Updated "Mortgage" for Dec 15.');

    expect(amountsByMonth($bill, ['2026-11', '2026-12', '2027-01']))
        ->toBe(['2026-11' => 185000, '2026-12' => 190000, '2027-01' => 185000]);
});

test('changing the amount from a month on leaves earlier months alone', function () {
    $bill = mortgage();

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2026-12-15', 'onward_amount' => '1910', 'month_amount' => ''])
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-11', '2026-12', '2027-06']))
        ->toBe(['2026-11' => 185000, '2026-12' => 191000, '2027-06' => 191000]);
});

test('a month\'s own amount wins over an amount change', function () {
    $bill = mortgage();
    $bill->amounts()->create(['effective_on' => '2026-12-15', 'amount_cents' => 191000]);
    $bill->exceptions()->create(['original_due_on' => '2027-01-15', 'amount_cents' => 150000]);

    expect(amountsByMonth($bill, ['2026-12', '2027-01', '2027-02']))
        ->toBe(['2026-12' => 191000, '2027-01' => 150000, '2027-02' => 191000]);
});

test('a bill whose amount changes each time asks for each month after the first', function () {
    $bill = hydro();

    expect(amountsByMonth($bill, ['2026-09', '2026-10', '2026-11']))
        ->toBe(['2026-09' => 12000, '2026-10' => null, '2026-11' => null]);

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2026-11-22', 'month_amount' => '134'])
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-10', '2026-11', '2026-12']))
        ->toBe(['2026-10' => null, '2026-11' => 13400, '2026-12' => null]);
});

test('All bills shows what\'s owed on each bill\'s next due date', function () {
    mortgage();
    hydro();

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('commitments.0.name', 'Hydro')
            ->where('commitments.0.next_amount_cents', null)
            ->where('commitments.1.next_amount_cents', 185000));
});

test('undoing a month\'s amount puts back what was there before', function () {
    $bill = mortgage();
    $bill->exceptions()->create(['original_due_on' => '2026-12-15', 'amount_cents' => 190000]);

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2026-12-15', 'onward_amount' => '1910', 'month_amount' => ''])
        ->assertInertiaFlash('toast.undo.url', route('finances.commitments.amounts.update', $bill))
        ->assertInertiaFlash('toast.undo.method', 'put')
        ->assertInertiaFlash('toast.undo.data', ['due_on' => '2026-12-15', 'month_amount' => '1900.00', 'onward_amount' => null]);

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2026-12-15', 'month_amount' => '1900.00', 'onward_amount' => null])
        ->assertSessionHasNoErrors();

    expect($bill->exceptions()->sole()->amount_cents)->toBe(190000)
        ->and($bill->amounts()->pluck('amount_cents')->all())->toBe([185000]);
});

test('an amount can only be set on one of the bill\'s due dates', function () {
    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', mortgage()), ['due_on' => '2026-12-14', 'month_amount' => '1900'])
        ->assertSessionHasErrors(['due_on' => 'That isn\'t one of this bill\'s due dates.']);
});

test('an amount must be a plain amount', function () {
    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', mortgage()), ['due_on' => '2026-12-15', 'month_amount' => 'lots'])
        ->assertSessionHasErrors(['month_amount' => 'Enter an amount like 19.99 or $1,200.']);
});

test('a bill whose amount changes each time has no amount from a month on', function () {
    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', hydro()), ['due_on' => '2026-11-22', 'onward_amount' => '130'])
        ->assertSessionHasErrors('onward_amount');
});

test('another family\'s bill amounts cannot be changed', function () {
    $theirs = Commitment::factory()->costing(5000)->create(['starts_on' => '2026-01-15']);

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $theirs), ['due_on' => '2026-12-15', 'month_amount' => '1'])
        ->assertNotFound();

    expect($theirs->exceptions()->count())->toBe(0);
});

/**
 * @return array<string, mixed>
 */
function editedBill(Commitment $bill, array $changes): array
{
    return [
        'name' => $bill->name,
        'category_id' => $bill->category_id,
        'payment_method_id' => '',
        'amount_varies' => $bill->amount_varies ? '1' : '0',
        'frequency' => $bill->frequency->value,
        'interval' => $bill->interval,
        'starts_on' => $bill->starts_on->toDateString(),
        'ends_on' => '',
        ...$changes,
    ];
}

test('a bill switched to the same amount each month carries its latest entered amount forward', function () {
    $mortgage = Commitment::factory()->create([
        'family_id' => $this->family->id,
        'category_id' => $this->category->id,
        'name' => 'Mortgage',
        'amount_varies' => true,
        'starts_on' => '2026-11-15',
    ]);
    $mortgage->exceptions()->create(['original_due_on' => '2026-11-15', 'amount_cents' => 185000]);

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $mortgage), editedBill($mortgage, ['amount_varies' => '0']))
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($mortgage, ['2026-11', '2026-12', '2027-06']))
        ->toBe(['2026-11' => 185000, '2026-12' => 185000, '2027-06' => 185000]);
});

test('months entered one at a time keep their own amounts after switching to the same amount each month', function () {
    $hydro = hydro();
    $hydro->exceptions()->create(['original_due_on' => '2026-10-22', 'amount_cents' => 13400]);

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $hydro), editedBill($hydro, ['amount_varies' => '0']))
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($hydro, ['2026-09', '2026-10', '2026-11']))
        ->toBe(['2026-09' => 12000, '2026-10' => 13400, '2026-11' => 13400]);
});

function decemberBill(): Commitment
{
    return Commitment::factory()->costing(9000)->create([
        'family_id' => test()->family->id,
        'category_id' => test()->category->id,
        'name' => 'Water',
        'starts_on' => '2026-12-05',
    ]);
}

test('a fixed bill moved to start earlier shows its amount in the earlier months', function () {
    $bill = decemberBill();

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), editedBill($bill, ['starts_on' => '2026-10-05']))
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-10', '2026-11', '2026-12', '2027-01']))
        ->toBe(['2026-10' => 9000, '2026-11' => 9000, '2026-12' => 9000, '2027-01' => 9000]);
});

test('switching to changing each time keeps every amount already shown and asks for later ones', function () {
    $bill = decemberBill();
    $this->actingAs($this->user)->patch(route('finances.commitments.update', $bill), editedBill($bill, ['starts_on' => '2026-10-05']));

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), editedBill($bill->fresh(), ['amount_varies' => '1']))
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-10', '2026-11', '2026-12', '2027-01']))
        ->toBe(['2026-10' => 9000, '2026-11' => 9000, '2026-12' => 9000, '2027-01' => null]);
});

test('switching to changing each time keeps a later month\'s amount entered for that month and the ones after', function () {
    $bill = decemberBill();
    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2027-02-05', 'onward_amount' => '95', 'month_amount' => '']);

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), editedBill($bill->fresh(), ['amount_varies' => '1']))
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-12', '2027-01', '2027-02', '2027-03']))
        ->toBe(['2026-12' => 9000, '2027-01' => null, '2027-02' => 9500, '2027-03' => null]);
});

test('months added by starting earlier while switching to changing each time ask for their amount', function () {
    $bill = decemberBill();
    $this->actingAs($this->user)->patch(route('finances.commitments.update', $bill), editedBill($bill, ['starts_on' => '2026-10-05']));

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), editedBill($bill->fresh(), ['amount_varies' => '1', 'starts_on' => '2026-09-05']))
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-09', '2026-10', '2026-11', '2026-12', '2027-01']))
        ->toBe(['2026-09' => null, '2026-10' => 9000, '2026-11' => 9000, '2026-12' => 9000, '2027-01' => null]);
});

test('switching back to the same amount each month tidies up, so a later change applies to every month after it', function () {
    $bill = decemberBill();
    $this->actingAs($this->user)->patch(route('finances.commitments.update', $bill), editedBill($bill, ['starts_on' => '2026-10-05', 'amount_varies' => '1']));

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), editedBill($bill->fresh(), ['amount_varies' => '0']))
        ->assertSessionHasNoErrors();

    expect($bill->exceptions()->count())->toBe(0);

    $this->actingAs($this->user)
        ->put(route('finances.commitments.amounts.update', $bill), ['due_on' => '2026-11-05', 'onward_amount' => '95', 'month_amount' => ''])
        ->assertSessionHasNoErrors();

    expect(amountsByMonth($bill, ['2026-10', '2026-11', '2026-12', '2027-01']))
        ->toBe(['2026-10' => 9000, '2026-11' => 9500, '2026-12' => 9500, '2027-01' => 9500]);
});
