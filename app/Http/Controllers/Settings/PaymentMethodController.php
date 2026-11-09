<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PaymentMethodStoreRequest;
use App\Http\Requests\Settings\PaymentMethodUpdateRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Payment methods are archived, never deleted, so old expenses keep theirs
 * (ADR 0007). Editing, archiving and unarchiving confirm with an undo toast.
 */
class PaymentMethodController extends Controller
{
    /**
     * Show the family's payment methods, archived ones included.
     */
    public function index(): Response
    {
        return Inertia::render('settings/PaymentMethods', [
            'paymentMethods' => PaymentMethod::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'kind', 'last_four', 'archived_at'])
                ->map(fn (PaymentMethod $method) => [
                    'id' => $method->id,
                    'name' => $method->name,
                    'kind' => $method->kind,
                    'last_four' => $method->last_four,
                    'archived' => $method->archived_at !== null,
                ]),
        ]);
    }

    /**
     * Add a payment method after the existing ones.
     */
    public function store(PaymentMethodStoreRequest $request): RedirectResponse
    {
        $paymentMethod = PaymentMethod::create([
            ...$request->validated(),
            'position' => (PaymentMethod::max('position') ?? -1) + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added ":name".', ['name' => $paymentMethod->name])]);

        return back();
    }

    /**
     * Rename a payment method or change its kind or last four digits.
     */
    public function update(PaymentMethodUpdateRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $previous = [
            'name' => $paymentMethod->name,
            'kind' => $paymentMethod->kind->value,
            'last_four' => $paymentMethod->last_four,
        ];

        $paymentMethod->update($request->validated());

        $this->toastWithUndo(__('Saved ":name".', ['name' => $paymentMethod->name]), [
            'url' => route('payment-methods.update', $paymentMethod),
            'method' => 'patch',
            'data' => $previous,
        ]);

        return back();
    }

    /**
     * Archive a payment method so it can't be picked for new expenses.
     */
    public function archive(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->forceFill(['archived_at' => now()])->save();

        $this->toastWithUndo(__('Archived ":name".', ['name' => $paymentMethod->name]), [
            'url' => route('payment-methods.unarchive', $paymentMethod),
            'method' => 'delete',
        ]);

        return back();
    }

    /**
     * Make an archived payment method pickable again.
     */
    public function unarchive(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->forceFill(['archived_at' => null])->save();

        $this->toastWithUndo(__('Unarchived ":name".', ['name' => $paymentMethod->name]), [
            'url' => route('payment-methods.archive', $paymentMethod),
            'method' => 'post',
        ]);

        return back();
    }
}
