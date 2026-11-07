<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\HouseUpdateRequest;
use App\Support\Tenancy\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HouseController extends Controller
{
    /**
     * Show the current house's settings page.
     */
    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('settings/House', [
            'house' => $tenant->house()->only(['id', 'name', 'address']),
        ]);
    }

    /**
     * Update the current house's details.
     */
    public function update(HouseUpdateRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->house()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('House updated.')]);

        return to_route('house.edit');
    }
}
