<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the signed-in member's family before route model binding runs, so
 * family-owned models bind within the right family.
 */
class ResolveTenant
{
    public function __construct(private Tenant $tenant) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->tenant->set($request->user()?->currentFamily());

        return $next($request);
    }
}
