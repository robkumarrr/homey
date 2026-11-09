<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CategoryStoreRequest;
use App\Http\Requests\Settings\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Categories are archived, never deleted, so old expenses keep theirs
 * (ADR 0007). Editing, archiving and unarchiving confirm with an undo toast.
 */
class CategoryController extends Controller
{
    /**
     * Show the family's categories, archived ones included.
     */
    public function index(): Response
    {
        return Inertia::render('settings/Categories', [
            'categories' => Category::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'icon', 'colour', 'archived_at'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'colour' => $category->colour,
                    'archived' => $category->archived_at !== null,
                ]),
        ]);
    }

    /**
     * Add a category after the existing ones.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $category = Category::create([
            ...$request->validated(),
            'position' => (Category::max('position') ?? -1) + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added ":name".', ['name' => $category->name])]);

        return back();
    }

    /**
     * Rename a category or change its icon or colour.
     */
    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $previous = [
            'name' => $category->name,
            'icon' => $category->icon->value,
            'colour' => $category->colour->value,
        ];

        $category->update($request->validated());

        $this->toastWithUndo(__('Saved ":name".', ['name' => $category->name]), [
            'url' => route('categories.update', $category),
            'method' => 'patch',
            'data' => $previous,
        ]);

        return back();
    }

    /**
     * Archive a category so it can't be picked for new expenses. The last
     * active category stays, so there's always one to pick.
     */
    public function archive(Category $category): RedirectResponse
    {
        if (Category::query()->whereNull('archived_at')->whereKeyNot($category->id)->doesntExist()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Keep at least one category.')]);

            return back();
        }

        $category->forceFill(['archived_at' => now()])->save();

        $this->toastWithUndo(__('Archived ":name".', ['name' => $category->name]), [
            'url' => route('categories.unarchive', $category),
            'method' => 'delete',
        ]);

        return back();
    }

    /**
     * Make an archived category pickable again.
     */
    public function unarchive(Category $category): RedirectResponse
    {
        $category->forceFill(['archived_at' => null])->save();

        $this->toastWithUndo(__('Unarchived ":name".', ['name' => $category->name]), [
            'url' => route('categories.archive', $category),
            'method' => 'post',
        ]);

        return back();
    }
}
