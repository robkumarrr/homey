<?php

use App\Models\PaymentMethod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
});

function aPaymentMethod(array $attributes = []): PaymentMethod
{
    return PaymentMethod::factory()->create([
        'family_id' => test()->family->id,
        'name' => 'TD Visa',
        'kind' => 'credit_card',
        'last_four' => '1234',
        ...$attributes,
    ]);
}

function paymentMethodInput(array $overrides = []): array
{
    return [
        'name' => 'WS Visa Infinite+',
        'kind' => 'credit_card',
        'last_four' => '9876',
        ...$overrides,
    ];
}

function freshPaymentMethod(PaymentMethod $paymentMethod): PaymentMethod
{
    return asFamily(test()->family, fn () => $paymentMethod->fresh());
}

test('the payment methods page lists the family\'s methods, archived ones included', function () {
    aPaymentMethod(['position' => 0]);
    aPaymentMethod(['name' => 'Old Amex', 'position' => 1, 'archived_at' => now()]);
    PaymentMethod::factory()->create(['name' => 'Not ours']);

    $this->actingAs($this->user)
        ->get(route('payment-methods.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/PaymentMethods')
            ->has('paymentMethods', 2)
            ->where('paymentMethods.0.name', 'TD Visa')
            ->where('paymentMethods.0.last_four', '1234')
            ->where('paymentMethods.1.name', 'Old Amex')
            ->where('paymentMethods.1.archived', true));
});

test('a member can add a payment method after the existing ones', function () {
    aPaymentMethod(['position' => 3]);

    $this->actingAs($this->user)
        ->from(route('payment-methods.index'))
        ->post(route('payment-methods.store'), paymentMethodInput())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('payment-methods.index'))
        ->assertInertiaFlash('toast.message', 'Added "WS Visa Infinite+".');

    $method = asFamily($this->family, fn () => PaymentMethod::where('name', 'WS Visa Infinite+')->sole());

    expect($method->kind->value)->toBe('credit_card')
        ->and($method->last_four)->toBe('9876')
        ->and($method->position)->toBe(4);
});

test('the last four digits are optional', function () {
    $this->actingAs($this->user)
        ->post(route('payment-methods.store'), paymentMethodInput(['name' => 'TD chequing', 'kind' => 'bank_account', 'last_four' => '']))
        ->assertSessionHasNoErrors();

    $method = asFamily($this->family, fn () => PaymentMethod::sole());

    expect($method->last_four)->toBeNull();
});

test('a payment method needs a name, a known kind and at most the last four digits', function (array $input, string $field, ?string $message) {
    $this->actingAs($this->user)
        ->post(route('payment-methods.store'), paymentMethodInput($input))
        ->assertSessionHasErrors($message ? [$field => $message] : $field);
})->with([
    'no name' => [['name' => ''], 'name', null],
    'unknown kind' => [['kind' => 'cash'], 'kind', null],
    'a full card number' => [['last_four' => '4111111111111111'], 'last_four', 'Enter just the last 4 digits.'],
    'letters' => [['last_four' => '12ab'], 'last_four', 'Enter just the last 4 digits.'],
]);

test('a name already used in the family is rejected, even by an archived method', function (bool $archived) {
    aPaymentMethod(['name' => 'WS Visa Infinite+', 'archived_at' => $archived ? now() : null]);

    $this->actingAs($this->user)
        ->post(route('payment-methods.store'), paymentMethodInput())
        ->assertSessionHasErrors(['name' => 'You already have a payment method called "WS Visa Infinite+". If it isn\'t listed, it\'s under Archived.']);
})->with(['active' => false, 'archived' => true]);

test('another family\'s payment method name can be reused', function () {
    PaymentMethod::factory()->create(['name' => 'WS Visa Infinite+']);

    $this->actingAs($this->user)
        ->post(route('payment-methods.store'), paymentMethodInput())
        ->assertSessionHasNoErrors();
});

test('a member can edit a payment method and undo it', function () {
    $method = aPaymentMethod();

    $this->actingAs($this->user)
        ->patch(route('payment-methods.update', $method), paymentMethodInput(['name' => 'TD Aeroplan Visa', 'last_four' => '']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Saved "TD Aeroplan Visa".')
        ->assertInertiaFlash('toast.undo.url', route('payment-methods.update', $method))
        ->assertInertiaFlash('toast.undo.method', 'patch')
        ->assertInertiaFlash('toast.undo.data', ['name' => 'TD Visa', 'kind' => 'credit_card', 'last_four' => '1234']);

    expect(freshPaymentMethod($method))
        ->name->toBe('TD Aeroplan Visa')
        ->last_four->toBeNull();
});

test('a payment method can keep its own name when edited', function () {
    $method = aPaymentMethod();

    $this->actingAs($this->user)
        ->patch(route('payment-methods.update', $method), paymentMethodInput(['name' => 'TD Visa']))
        ->assertSessionHasNoErrors();
});

test('archiving a payment method can be undone by unarchiving it', function () {
    $method = aPaymentMethod();

    $this->actingAs($this->user)
        ->post(route('payment-methods.archive', $method))
        ->assertInertiaFlash('toast.message', 'Archived "TD Visa".')
        ->assertInertiaFlash('toast.undo.url', route('payment-methods.unarchive', $method))
        ->assertInertiaFlash('toast.undo.method', 'delete');

    expect(freshPaymentMethod($method)->archived_at)->not->toBeNull();

    $this->actingAs($this->user)
        ->delete(route('payment-methods.unarchive', $method))
        ->assertInertiaFlash('toast.message', 'Unarchived "TD Visa".')
        ->assertInertiaFlash('toast.undo.url', route('payment-methods.archive', $method))
        ->assertInertiaFlash('toast.undo.method', 'post');

    expect(freshPaymentMethod($method)->archived_at)->toBeNull();
});

test('another family\'s payment method cannot be changed', function (string $method, string $route) {
    $theirs = PaymentMethod::factory()->create();

    $this->actingAs($this->user)
        ->{$method}(route($route, $theirs), paymentMethodInput())
        ->assertNotFound();

    expect(asFamily($theirs->family, fn () => $theirs->fresh())->archived_at)->toBeNull();
})->with([
    'edit' => ['patch', 'payment-methods.update'],
    'archive' => ['post', 'payment-methods.archive'],
    'unarchive' => ['delete', 'payment-methods.unarchive'],
]);

test('guests are redirected to the login page from payment methods', function () {
    $this->get(route('payment-methods.index'))->assertRedirect(route('login'));
});
