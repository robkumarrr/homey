<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /**
     * Confirm a change with a toast whose Undo button sends the given request.
     *
     * @param  array{url: string, method: string, data?: array<string, mixed>}  $undo
     */
    protected function toastWithUndo(string $message, array $undo): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message, 'undo' => $undo]);
    }
}
