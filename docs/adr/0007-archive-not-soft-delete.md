# Records are archived with archived_at, not soft-deleted

Removing anything that history points at, such as a house, category, commitment, payment method, pet, contact or contact role, sets `archived_at`. The record stays visible to history but drops out of everyday lists. We rejected Laravel's `SoftDeletes` for three reasons. Its global scope makes `$expense->category` return null for a soft-deleted category, so historical reports drop rows or crash. Hidden rows still collide with unique indexes, such as one payment per occurrence. And raw `DB::table()` sums ignore it anyway. `archived_at` has no global scope, so history always resolves.

## Consequences

Records of things that happened, like expenses, are deleted for real when entered by mistake, after a copy goes to Recently deleted (ADR 0011). Foreign keys restrict deleting anything that history depends on, so destroy actions archive instead.
