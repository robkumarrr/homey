<?php

namespace App\Actions;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateFamily
{
    /**
     * Create a family owned by the given member, with its first house.
     */
    public function handle(User $owner): Family
    {
        return DB::transaction(function () use ($owner): Family {
            $family = Family::create([
                'name' => Str::before($owner->name, ' ')."'s family",
            ]);

            $family->members()->attach($owner, ['role' => FamilyRole::Owner]);

            $family->houses()->create(['name' => 'Home']);

            return $family;
        });
    }
}
