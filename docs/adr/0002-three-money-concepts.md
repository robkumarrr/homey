# Owed, spent and planned are three separate records

Money appears in three forms: what the family has promised to pay (a **commitment**), what actually left the household (an **expense**) and what it plans to spend (a **budget allocation**). Each is its own record. A single "transaction" table with a type column would let forecasts leak into totals. The `expenses` table is the only place that stores money that moved. A commitment never creates expense rows ahead of time, because a future expense is a lie that every sum would have to filter out. Upcoming occurrences are computed from the schedule instead.

## Consequences

Other tables never store their own amount. A utility bill or a task's cost is an expense, with a detail row pointing at it. That keeps budget-versus-actual to one query over `expenses`, grouped by category.
