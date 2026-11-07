<?php

namespace App\Support\Tenancy;

use RuntimeException;

/**
 * Thrown when family-owned data is touched before a family is resolved.
 *
 * Failing loudly is deliberate: a scope that quietly returns every family's
 * rows would be a cross-family data leak (ADR 0006).
 */
class TenantNotResolved extends RuntimeException
{
    public static function make(): self
    {
        return new self('No family is resolved. Use Tenant::for() outside a request.');
    }
}
