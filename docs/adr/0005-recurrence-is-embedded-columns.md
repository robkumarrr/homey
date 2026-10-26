# Recurrence is a set of columns on each record, not a shared table

Commitments, income and tasks all repeat. Each stores its own schedule in the same set of columns, added by one trait: mode (`anchored`, `floating` or `once`), frequency, interval, the anchor day, weekday and month, plus an indexed `next_due_on`. A shared `schedules` table looked tidier, but a recurrence is a value, not an entity, and every consumer needs its own indexed `next_due_on` to list what's due. Embedding avoids a join on every agenda query. Changes to single dates are handled separately (see ADR 0001).

## Consequences

The date maths lives in one pure calculator class. It's the one place in the app where unit tests pay off, especially for day-31 months, which clamp to the last day of the month.
