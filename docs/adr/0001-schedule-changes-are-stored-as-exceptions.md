# Schedule changes are stored as exceptions

Commitments and income repeat on a schedule, but single dates still change: a payday shifted for a holiday, a skipped month of Netflix, a bill due early. We store only the change, as an exception tied to the commitment or income and keyed on the occurrence's original date (moved to a new date, given a different amount, or skipped). The schedule rule stays the source of truth and every other occurrence is computed from it. A one-off, like a Christmas bonus, is a schedule that happens once, not an exception.

## Considered Options

- **Materialize every occurrence as a row.** This makes queries simple, but it needs a job to generate rows ahead of time, and editing the schedule means rewriting every future row without losing the ones already paid.
- **Edit the schedule itself.** This would shift every later date, when only one date was meant to change.

## Consequences

An expense that pays an occurrence records the commitment and the occurrence's original due date. That way, moving the date later doesn't orphan the payment.
