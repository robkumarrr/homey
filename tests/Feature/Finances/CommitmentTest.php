<?php

use App\Enums\RepeatFrequency;
use App\Models\Category;
use App\Models\Commitment;
use App\Models\PaymentMethod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->category = asFamily($this->family, fn () => $this->family->categories()->where('name', 'Subscriptions')->sole());
});

function billInput(array $overrides = []): array
{
    return [
        'name' => 'Netflix',
        'amount' => '20.99',
        'category_id' => test()->category->id,
        'payment_method_id' => '',
        'frequency' => 'monthly',
        'interval' => 1,
        'starts_on' => '2026-11-15',
        'ends_on' => '',
        ...$overrides,
    ];
}

function aBill(array $attributes = [], ?int $amountCents = 2099): Commitment
{
    $factory = Commitment::factory();

    return ($amountCents === null ? $factory : $factory->costing($amountCents))->create([
        'family_id' => test()->family->id,
        'category_id' => test()->category->id,
        'name' => 'Netflix',
        ...$attributes,
    ]);
}

function freshBill(Commitment $commitment): Commitment
{
    return asFamily(test()->family, fn () => $commitment->fresh());
}

test('a member can add a bill', function () {
    $method = PaymentMethod::factory()->create(['family_id' => $this->family->id]);

    $this->actingAs($this->user)
        ->from(route('finances'))
        ->post(route('finances.commitments.store'), billInput([
            'amount' => '$1,850',
            'payment_method_id' => $method->id,
            'frequency' => 'weekly',
            'interval' => 2,
            'ends_on' => '2027-11-15',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('finances'))
        ->assertInertiaFlash('toast.message', 'Added "Netflix".');

    $bill = asFamily($this->family, fn () => Commitment::sole());

    expect($bill->amounts->sole()->only(['effective_on', 'amount_cents']))->toEqual(['effective_on' => $bill->starts_on, 'amount_cents' => 185000])
        ->and($bill->exceptions)->toBeEmpty()
        ->and($bill->amount_varies)->toBeFalse()
        ->and($bill->category_id)->toBe($this->category->id)
        ->and($bill->payment_method_id)->toBe($method->id)
        ->and($bill->frequency)->toBe(RepeatFrequency::Weekly)
        ->and($bill->interval)->toBe(2)
        ->and($bill->starts_on->toDateString())->toBe('2026-11-15')
        ->and($bill->ends_on->toDateString())->toBe('2027-11-15');
});

test('a bill whose amount changes each time keeps its amount for the first bill only', function () {
    $this->actingAs($this->user)
        ->post(route('finances.commitments.store'), billInput(['name' => 'Hydro', 'amount' => '120', 'amount_varies' => '1']))
        ->assertSessionHasNoErrors();

    $bill = asFamily($this->family, fn () => Commitment::sole());

    expect($bill->amount_varies)->toBeTrue()
        ->and($bill->amounts)->toBeEmpty()
        ->and($bill->exceptions->sole()->only(['original_due_on', 'amount_cents']))->toEqual(['original_due_on' => $bill->starts_on, 'amount_cents' => 12000]);
});

test('a bill whose amount changes each time can be added without an amount', function () {
    $this->actingAs($this->user)
        ->post(route('finances.commitments.store'), billInput(['amount' => '', 'amount_varies' => '1']))
        ->assertSessionHasNoErrors();

    $bill = asFamily($this->family, fn () => Commitment::sole());

    expect($bill->amounts)->toBeEmpty()
        ->and($bill->exceptions)->toBeEmpty();
});

test('a one-off bill ignores a final payment date', function () {
    $this->actingAs($this->user)
        ->post(route('finances.commitments.store'), billInput(['frequency' => 'once', 'ends_on' => '2026-01-01']))
        ->assertSessionHasNoErrors();

    expect(asFamily($this->family, fn () => Commitment::sole()->ends_on))->toBeNull();
});

test('a bill needs a name, an amount, a known schedule and a sensible final payment date', function (array $input, string $field) {
    $this->actingAs($this->user)
        ->post(route('finances.commitments.store'), billInput($input))
        ->assertSessionHasErrors($field);

    expect(asFamily($this->family, fn () => Commitment::count()))->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'no amount' => [['amount' => ''], 'amount'],
    'not an amount' => [['amount' => 'abc'], 'amount'],
    'unknown frequency' => [['frequency' => 'daily'], 'frequency'],
    'interval too large' => [['interval' => 13], 'interval'],
    'no first due date' => [['starts_on' => ''], 'starts_on'],
    'final payment before the first' => [['ends_on' => '2026-11-01'], 'ends_on'],
]);

test('another family\'s or an archived category or payment method cannot be used', function (string $field, Closure $record) {
    $this->actingAs($this->user)
        ->post(route('finances.commitments.store'), billInput([$field => $record()->id]))
        ->assertSessionHasErrors($field);
})->with([
    'another family\'s category' => ['category_id', fn () => Category::factory()->create()],
    'an archived category' => ['category_id', fn () => Category::factory()->archived()->create(['family_id' => test()->family->id])],
    'another family\'s payment method' => ['payment_method_id', fn () => PaymentMethod::factory()->create()],
    'an archived payment method' => ['payment_method_id', fn () => PaymentMethod::factory()->archived()->create(['family_id' => test()->family->id])],
]);

test('a member can edit a bill and undo it with its previous values, leaving its amounts alone', function () {
    $bill = aBill(['starts_on' => '2026-01-15']);

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), billInput(['name' => 'Netflix Premium', 'amount' => '24.99', 'frequency' => 'yearly']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Saved "Netflix Premium".')
        ->assertInertiaFlash('toast.undo.url', route('finances.commitments.update', $bill))
        ->assertInertiaFlash('toast.undo.method', 'patch')
        ->assertInertiaFlash('toast.undo.data', [
            'name' => 'Netflix',
            'amount_varies' => 0,
            'category_id' => $this->category->id,
            'payment_method_id' => null,
            'frequency' => 'monthly',
            'interval' => 1,
            'starts_on' => '2026-01-15',
            'ends_on' => null,
        ]);

    expect(freshBill($bill))
        ->name->toBe('Netflix Premium')
        ->amounts->sole()->amount_cents->toBe(2099)
        ->frequency->toBe(RepeatFrequency::Yearly)
        ->starts_on->toDateString()->toBe('2026-11-15');
});

test('editing a bill can keep its archived category and payment method', function () {
    $category = Category::factory()->archived()->create(['family_id' => $this->family->id]);
    $method = PaymentMethod::factory()->archived()->create(['family_id' => $this->family->id]);
    $bill = aBill(['category_id' => $category->id, 'payment_method_id' => $method->id]);

    $this->actingAs($this->user)
        ->patch(route('finances.commitments.update', $bill), billInput(['category_id' => $category->id, 'payment_method_id' => $method->id]))
        ->assertSessionHasNoErrors();
});

test('archiving a bill can be undone by unarchiving it', function () {
    $bill = aBill();

    $this->actingAs($this->user)
        ->post(route('finances.commitments.archive', $bill))
        ->assertInertiaFlash('toast.message', 'Archived "Netflix".')
        ->assertInertiaFlash('toast.undo.url', route('finances.commitments.unarchive', $bill))
        ->assertInertiaFlash('toast.undo.method', 'delete');

    expect(freshBill($bill)->archived_at)->not->toBeNull();

    $this->actingAs($this->user)
        ->delete(route('finances.commitments.unarchive', $bill))
        ->assertInertiaFlash('toast.message', 'Unarchived "Netflix".')
        ->assertInertiaFlash('toast.undo.url', route('finances.commitments.archive', $bill))
        ->assertInertiaFlash('toast.undo.method', 'post');

    expect(freshBill($bill)->archived_at)->toBeNull();
});

test('another family\'s bill cannot be changed', function (string $method, string $route) {
    $theirs = Commitment::factory()->create(['name' => 'Theirs']);

    $this->actingAs($this->user)
        ->{$method}(route($route, $theirs), billInput())
        ->assertNotFound();

    $stored = asFamily($theirs->family, fn () => $theirs->fresh());

    expect($stored->name)->toBe('Theirs')
        ->and($stored->archived_at)->toBeNull();
})->with([
    'edit' => ['patch', 'finances.commitments.update'],
    'archive' => ['post', 'finances.commitments.archive'],
    'unarchive' => ['delete', 'finances.commitments.unarchive'],
]);

test('guests cannot add bills', function () {
    $this->post(route('finances.commitments.store'), billInput())->assertRedirect(route('login'));
});

test('finances lists the month\'s due dates of every bill, computed from its schedule', function () {
    $this->travelTo('2026-11-08');
    $netflix = aBill(['starts_on' => '2026-01-15']);
    $groceries = aBill(['name' => 'Grocery box', 'frequency' => 'weekly', 'interval' => 2, 'starts_on' => '2026-10-30']);
    aBill(['name' => 'Insurance', 'frequency' => 'yearly', 'starts_on' => '2026-03-01']);
    Commitment::factory()->create(['name' => 'Not ours', 'starts_on' => '2026-11-01']);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('commitments', 3)
            ->where('occurrences', [
                ['commitment_id' => $groceries->id, 'due_on' => '2026-11-13', 'amount_cents' => 2099],
                ['commitment_id' => $netflix->id, 'due_on' => '2026-11-15', 'amount_cents' => 2099],
                ['commitment_id' => $groceries->id, 'due_on' => '2026-11-27', 'amount_cents' => 2099],
            ])
            ->where('commitments.1.name', 'Insurance')
            ->where('commitments.1.next_due_on', '2027-03-01')
            ->where('commitments.2.next_due_on', '2026-11-15'));
});

test('a bill stops after its final payment date', function () {
    $this->travelTo('2026-11-08');
    aBill(['starts_on' => '2026-01-15', 'ends_on' => '2026-10-15']);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('occurrences', [])
            ->where('commitments.0.next_due_on', null));

    $this->actingAs($this->user)
        ->get(route('finances', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page->has('occurrences', 1));
});

test('an archived bill stops from the day it was archived but stays in earlier months', function () {
    $this->travelTo('2026-11-10');
    $bill = aBill(['starts_on' => '2026-01-15', 'archived_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('occurrences', [])
            ->where('commitments.0.archived', true));

    $this->actingAs($this->user)
        ->get(route('finances', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('occurrences', [['commitment_id' => $bill->id, 'due_on' => '2026-10-15', 'amount_cents' => 2099]]));
});
