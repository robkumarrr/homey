# A bill's amount changes from a due date on, never backwards

A bill's amount can change: a variable-rate mortgage after a rate change, or a subscription's new price. Changing it must never rewrite months before the change. So a bill keeps a short history in `commitment_amounts`, one row per change, each applying from one of its due dates (`effective_on`) until the next change. A single month that differs, like December's hydro bill, is a commitment exception for just that date (ADR 0001).

What a due date owes is worked out when it's shown: its own exception amount if it has one, otherwise the latest change on or before that date. The first change always applies from the bill's first due date, and moves with it if the start moves earlier. A bill whose amount changes each time (`amount_varies`) only uses its dates' own amounts. Each date needs its own amount, entered when the bill arrives, and until then it shows "Enter amount". The amount typed when adding a bill becomes either its first change (fixed bills) or the first date's exception (bills that change each time).

Switching a bill between the two never blanks an amount already shown. When a fixed bill starts changing each time, each due date up to the end of this month, or up to its next bill if that's later, keeps the amount it showed as its own exception. Later dates ask for theirs. When a bill stops changing each time, its latest entered amount carries forward if it has no changes yet, and exceptions that now match anyway are dropped, so a later "this and later months" change isn't blocked by them.

## Considered Options

- **One amount column on the bill.** Simplest, but editing it would change every month at once, including past ones that were never paid.
- **Store each month as a row.** This needs rows generated ahead of time, which ADR 0002 rules out.

## Consequences

Edit bill doesn't change amounts. Amounts are changed from a due date's menu, either for just that month or for that month and later ones. Paying records what was actually paid as an expense (ADR 0002), so a paid month keeps its payment even if the amount owed changes later.
