<?php

use App\Models\DeletedExpense;
use App\Models\Expense;
use App\Models\Scopes\FamilyScope;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->category = asFamily($this->family, fn () => $this->family->categories()->firstOrFail());
    $this->expense = Expense::factory()->create([
        'family_id' => $this->family->id,
        'category_id' => $this->category->id,
        'name' => 'Loblaws',
        'amount_cents' => 14237,
        'paid_on' => '2026-11-03',
    ]);
});

test('deleting an expense moves it to Recently deleted, with an undo', function () {
    $response = $this->actingAs($this->user)
        ->from(route('finances'))
        ->delete(route('finances.expenses.destroy', $this->expense))
        ->assertRedirect(route('finances'));

    $deleted = asFamily($this->family, fn () => DeletedExpense::sole());

    expect(Expense::withoutGlobalScope(FamilyScope::class)->count())->toBe(0)
        ->and($deleted->expense_id)->toBe($this->expense->id)
        ->and($deleted->payload['name'])->toBe('Loblaws')
        ->and($deleted->deleted_by)->toBe($this->user->id);

    $response->assertInertiaFlash('toast.message', 'Deleted "Loblaws".')
        ->assertInertiaFlash('toast.undo.url', route('finances.deleted-expenses.restore', $deleted))
        ->assertInertiaFlash('toast.undo.method', 'post');
});

test('restoring brings the expense back exactly as it was', function () {
    $original = asFamily($this->family, fn () => $this->expense->fresh()->getAttributes());
    $this->travel(5)->minutes();

    $this->actingAs($this->user)->delete(route('finances.expenses.destroy', $this->expense));
    $deleted = asFamily($this->family, fn () => DeletedExpense::sole());

    $this->actingAs($this->user)
        ->post(route('finances.deleted-expenses.restore', $deleted))
        ->assertInertiaFlash('toast.message', 'Restored "Loblaws".');

    $restored = asFamily($this->family, fn () => Expense::sole());

    expect($restored->getAttributes())->toEqual($original)
        ->and(DeletedExpense::withoutGlobalScope(FamilyScope::class)->count())->toBe(0);
});

test('another family\'s expense cannot be deleted', function () {
    $other = Expense::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('finances.expenses.destroy', $other))
        ->assertNotFound();

    expect(Expense::withoutGlobalScope(FamilyScope::class)->whereKey($other->id)->exists())->toBeTrue();
});

test('another family\'s deleted expense cannot be restored', function () {
    $other = DeletedExpense::factory()->create();

    $this->actingAs($this->user)
        ->post(route('finances.deleted-expenses.restore', $other))
        ->assertNotFound();

    expect(DeletedExpense::withoutGlobalScope(FamilyScope::class)->whereKey($other->id)->exists())->toBeTrue();
});

test('finances lists the family\'s recently deleted expenses, newest first', function () {
    $older = DeletedExpense::factory()->create(['family_id' => $this->family->id, 'deleted_at' => now()->subDays(3)]);
    $newer = DeletedExpense::factory()->create(['family_id' => $this->family->id, 'deleted_at' => now()->subHour()]);
    DeletedExpense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('finances'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('deletedExpenses', 2)
            ->where('deletedExpenses.0.id', $newer->id)
            ->where('deletedExpenses.0.name', $newer->payload['name'])
            ->where('deletedExpenses.1.id', $older->id));
});

test('deleted expenses are purged after 30 days', function () {
    $expired = DeletedExpense::factory()->create(['deleted_at' => now()->subDays(31)]);
    $kept = DeletedExpense::factory()->create(['deleted_at' => now()->subDays(29)]);

    $this->artisan('model:prune', ['--model' => [DeletedExpense::class]])->assertSuccessful();

    expect(DeletedExpense::withoutGlobalScope(FamilyScope::class)->pluck('id')->all())->toBe([$kept->id])
        ->and($expired->id)->not->toBe($kept->id);
});
