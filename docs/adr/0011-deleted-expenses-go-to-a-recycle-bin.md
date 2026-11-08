# Deleted expenses go to a recycle bin table

Deleting an expense moves it to **Recently deleted** for 30 days, where it can be restored. The expense row is copied as a JSON snapshot into `deleted_expenses` and then really deleted from `expenses`. Restoring inserts the snapshot back under its original id. A daily `model:prune` purges snapshots older than 30 days.

We rejected a `deleted_at` column on `expenses`, with or without `SoftDeletes`, for the same reasons as ADR 0007. Every sum, report and raw query would have to remember to leave hidden rows out. Hidden rows would also still collide with unique indexes, such as one payment per occurrence. With a separate table, `expenses` only ever holds money that moved, and nothing has to filter it.

## Consequences

The snapshot is the row's raw attributes, so a restored expense comes back with its original id, category, dates and timestamps. A restore fails if the expense's category has since been deleted, but categories are archived, never deleted (ADR 0007). Any future column on `expenses` is captured automatically. A column removed later is the one case where restoring an old snapshot needs care.
