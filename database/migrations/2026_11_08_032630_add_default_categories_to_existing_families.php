<?php

use App\Actions\CreateDefaultCategories;
use App\Models\Family;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give families created before categories existed their default set.
     */
    public function up(): void
    {
        $familyIds = DB::table('families')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('categories')
                ->whereColumn('categories.family_id', 'families.id'))
            ->pluck('id');

        foreach (Family::query()->findMany($familyIds) as $family) {
            app(CreateDefaultCategories::class)->handle($family);
        }
    }
};
