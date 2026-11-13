# Recurrence is a set of columns on each record, not a shared table

Commitments, income and tasks all repeat. Each stores its own schedule in the same set of columns: the first date (`starts_on`), a frequency (`once`, `weekly`, `monthly` or `yearly`), an interval (every 2 weeks, every 3 months) and an optional last date (`ends_on`). The day of the month, weekday and month a schedule repeats on all come from its first date. An indexed `next_due_on` gets added once something needs to list what's due across records, such as the Dashboard agenda or autopay, and tasks will add a floating mode that counts from when they were last done. A shared `schedules` table looked tidier, but a recurrence is a value, not an entity, and every consumer needs its own indexed `next_due_on` to list what's due. Embedding avoids a join on every agenda query. Changes to single dates are handled separately (see ADR 0001).

## Consequences

The date maths lives in one pure calculator class. It's the one place in the app where unit tests pay off, especially for day-31 months, which clamp to the last day of the month.
