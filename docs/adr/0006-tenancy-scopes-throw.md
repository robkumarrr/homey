# Tenancy scopes throw when no family is resolved

Family- and house-owned models get global scopes that filter reads and fill in the owner on create. If no family is resolved, for example in a job, a seeder or a console command, the scope **throws**. It doesn't quietly return everything. A scope that does nothing when there's no tenant is a silent cross-family data leak, and with financial data a crash is the better failure. Code outside a request sets the tenant explicitly with `Tenant::for($family, fn () => ...)`.

## Consequences

The only allowed escape hatch is removing one named scope (`withoutGlobalScope(FamilyScope::class)`), never `withoutGlobalScopes()`. Validation rules like `exists` must go through the scoped query, or they will accept another family's ids.
