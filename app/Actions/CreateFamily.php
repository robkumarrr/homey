<?php

namespace App\Actions;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateFamily
{
    public function __construct(private CreateDefaultCategories $createDefaultCategories) {}

    /**
     * Create a family owned by the given member, with its first house and
     * the default categories.
     */
    public function handle(User $owner): Family
    {
        return DB::transaction(function () use ($owner): Family {
            $family = Family::create([
                'name' => Str::before($owner->name, ' ')."'s family",
            ]);

            $family->members()->attach($owner, ['role' => FamilyRole::Owner]);

            $family->houses()->create(['name' => 'Our house']);

            $this->createDefaultCategories->handle($family);

            return $family;
        });
    }
}
