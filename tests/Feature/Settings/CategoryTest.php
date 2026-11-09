<?php

use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->family = $this->user->currentFamily();
    $this->category = asFamily($this->family, fn () => $this->family->categories()->where('name', 'Dining')->sole());
});

function ours(Category $category): Category
{
    return asFamily(test()->family, fn () => $category->fresh());
}

function categoryInput(array $overrides = []): array
{
    return [
        'name' => 'Kids',
        'icon' => 'baby',
        'colour' => 'rose',
        ...$overrides,
    ];
}

test('the categories page lists the family\'s categories, archived ones included', function () {
    Category::factory()->archived()->create(['family_id' => $this->family->id, 'name' => 'Gym', 'position' => 20]);
    Category::factory()->create(['name' => 'Not ours']);

    $this->actingAs($this->user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Categories')
            ->has('categories', 10)
            ->where('categories.0.name', 'Housing')
            ->where('categories.9.name', 'Gym')
            ->where('categories.9.archived', true));
});

test('a member can add a category after the existing ones', function () {
    $this->actingAs($this->user)
        ->from(route('categories.index'))
        ->post(route('categories.store'), categoryInput())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'))
        ->assertInertiaFlash('toast.message', 'Added "Kids".');

    $category = asFamily($this->family, fn () => Category::where('name', 'Kids')->sole());

    expect($category->icon->value)->toBe('baby')
        ->and($category->colour->value)->toBe('rose')
        ->and($category->position)->toBe(9)
        ->and($category->archived_at)->toBeNull();
});

test('a category needs a name and an icon and colour from the list', function (array $input, string $field) {
    $this->actingAs($this->user)
        ->post(route('categories.store'), categoryInput($input))
        ->assertSessionHasErrors($field);
})->with([
    'no name' => [['name' => ''], 'name'],
    'unknown icon' => [['icon' => 'rocket'], 'icon'],
    'unknown colour' => [['colour' => 'gold'], 'colour'],
]);

test('a name already used in the family is rejected, even by an archived category', function (bool $archived) {
    Category::factory()->state($archived ? ['archived_at' => now()] : [])
        ->create(['family_id' => $this->family->id, 'name' => 'Kids']);

    $this->actingAs($this->user)
        ->post(route('categories.store'), categoryInput())
        ->assertSessionHasErrors(['name' => 'You already have a category called "Kids". If it isn\'t listed, it\'s under Archived.']);
})->with(['active' => false, 'archived' => true]);

test('another family\'s category name can be reused', function () {
    Category::factory()->create(['name' => 'Kids']);

    $this->actingAs($this->user)
        ->post(route('categories.store'), categoryInput())
        ->assertSessionHasNoErrors();
});

test('a member can edit a category and undo it', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('categories.update', $this->category), categoryInput(['name' => 'Eating out']))
        ->assertSessionHasNoErrors();

    $response->assertInertiaFlash('toast.message', 'Saved "Eating out".')
        ->assertInertiaFlash('toast.undo.url', route('categories.update', $this->category))
        ->assertInertiaFlash('toast.undo.method', 'patch')
        ->assertInertiaFlash('toast.undo.data', ['name' => 'Dining', 'icon' => 'utensils-crossed', 'colour' => 'pink']);

    expect(ours($this->category))
        ->name->toBe('Eating out')
        ->icon->value->toBe('baby')
        ->colour->value->toBe('rose');
});

test('a category can keep its own name when edited', function () {
    $this->actingAs($this->user)
        ->patch(route('categories.update', $this->category), categoryInput(['name' => 'Dining']))
        ->assertSessionHasNoErrors();
});

test('archiving a category can be undone by unarchiving it', function () {
    $this->actingAs($this->user)
        ->post(route('categories.archive', $this->category))
        ->assertInertiaFlash('toast.message', 'Archived "Dining".')
        ->assertInertiaFlash('toast.undo.url', route('categories.unarchive', $this->category))
        ->assertInertiaFlash('toast.undo.method', 'delete');

    expect(ours($this->category)->archived_at)->not->toBeNull();

    $this->actingAs($this->user)
        ->delete(route('categories.unarchive', $this->category))
        ->assertInertiaFlash('toast.message', 'Unarchived "Dining".')
        ->assertInertiaFlash('toast.undo.url', route('categories.archive', $this->category))
        ->assertInertiaFlash('toast.undo.method', 'post');

    expect(ours($this->category)->archived_at)->toBeNull();
});

test('the last active category cannot be archived', function () {
    asFamily($this->family, fn () => Category::whereKeyNot($this->category->id)->update(['archived_at' => now()]));

    $this->actingAs($this->user)
        ->post(route('categories.archive', $this->category))
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'Keep at least one category.');

    expect(ours($this->category)->archived_at)->toBeNull();
});

test('another family\'s category cannot be changed', function (string $method, string $route) {
    $theirs = Category::factory()->create();

    $this->actingAs($this->user)
        ->{$method}(route($route, $theirs), categoryInput())
        ->assertNotFound();

    expect(asFamily($theirs->family, fn () => $theirs->fresh())->archived_at)->toBeNull();
})->with([
    'edit' => ['patch', 'categories.update'],
    'archive' => ['post', 'categories.archive'],
    'unarchive' => ['delete', 'categories.unarchive'],
]);

test('guests are redirected to the login page from categories', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
});
