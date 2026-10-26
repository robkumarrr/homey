# Homey

A home-management app for families: what the household owes, spends, maintains and plans, across one or more houses.

## Households

**Family**:
The household that owns everything in Homey; people log in as members of a family.
_Avoid_: Account, household, tenant

**Member**:
A person with a login who belongs to a family. The member who created the family is its owner.
_Avoid_: User (in UI copy), account holder

**House**:
A property a family owns or lives in. A family has one or more houses.
_Avoid_: Home, property, residence

**Join code**:
A single-use code a family owner shares so a new person can sign up straight into that family. Using it consumes it and a fresh one is issued.
_Avoid_: Invite code, family code

## Money

**Commitment**:
A recurring promise to pay: a subscription, bill, loan or insurance premium, with an expected amount and schedule. It records what is owed, never what was paid.
_Avoid_: Bill (for the template), subscription (as the general term), recurring expense

**Expense**:
Money that actually left the household, on a specific date. The only record of real spending.
_Avoid_: Transaction, payment, charge, purchase

**Occurrence**:
One date of a commitment or an income, such as "the October Netflix". A single occurrence can be moved, re-priced or skipped without changing the schedule. A commitment's occurrence is paid when an expense is recorded against it.
_Avoid_: Instance, cycle

**Category**:
A family's label for grouping spending, such as Groceries or Utilities. Every expense has exactly one.
_Avoid_: Tag, type, bucket

**Budget allocation**:
The amount a family plans to spend in one category over one budget period.
_Avoid_: Budget line, envelope, limit

**Cash basis**:
Spending counts in the month it was paid, not the month it was due.

**Income**:
Money the household expects to earn, on a schedule like pay or once like a bonus, optionally tied to the member who earns it (the earner). Homey tracks income and spending, not what is held in bank accounts.
_Avoid_: Revenue, earnings, balance

## Scheduling

**Task**:
A job to get done for a house or a pet, like draining the hot water tank or giving flea medication. Anything that repeats is a task, including "book Rosie's checkup".
_Avoid_: Event, maintenance item, upkeep, to-do

**Appointment**:
A single date and time to be somewhere or meet someone, for a house, a pet or a project, like Rosie's checkup or a contractor's site visit. Appointments never repeat.
_Avoid_: Event, booking, visit

**Agenda**:
The Home screen's list of what needs attention soon: due tasks, appointments and occurrences together.
_Avoid_: Event, feed, timeline

**Anchored schedule**:
Repeats on fixed calendar dates regardless of when it was last done, like a bill due on the 5th.

**Floating schedule**:
Repeats a set interval after it was last completed, like a furnace filter every three months.

## Records

**Archived**:
Retired from everyday views but kept, with all its history intact. Archiving is how things are removed.
_Avoid_: Deleted, inactive

**Project**:
A one-off piece of work on a house, like replacing the eavestroughs. It is a project, not a task, when it involves a quote, a contract or more than one payment.
_Avoid_: Renovation (as the general term), job

**Contractor**:
A person or company hired to work on a house.
_Avoid_: Vendor, tradesperson

**Document**:
A file attached to a record: a quote, invoice, receipt, warranty, permit or photo. A newer document can supersede an older one without replacing it.
_Avoid_: Attachment (in UI copy), file
