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
A promise to pay on a schedule, usually recurring: a subscription, bill, loan or insurance premium, with an expected amount (or one entered each time, for bills like hydro). It records what is owed, never what was paid, and can stop after a final payment date. The app calls it a Bill; "commitment" is the name in code.
_Avoid_: Subscription (as the general term), recurring expense

**Amount change**:
A new amount for a commitment from one of its due dates on, such as a mortgage after a rate change. Earlier dates keep the amount before it.
_Avoid_: Price change, re-price (which is for one occurrence)

**Occurrence**:
One date of a commitment or an income, such as "the October Netflix". A single occurrence can be moved, re-priced or skipped without changing the schedule. A commitment's occurrence is paid when an expense is recorded against it.
_Avoid_: Instance, cycle

**Autopay**:
A setting on a fixed-amount commitment, like the mortgage or Netflix. On each due date Homey records the expense itself, using that occurrence's amount and the commitment's payment method and contact. Variable bills, like hydro, still ask how much was paid.
_Avoid_: Auto-debit, automatic payment

**Expense**:
Money that actually left the household, on a specific date. The only record of real spending. It can record the payment method that paid it and the contact it was paid to.
_Avoid_: Transaction, payment, charge, purchase

**Payment method**:
A specific bank account or credit card the family pays from, such as "TD chequing" or "WS Visa Infinite+". Homey keeps a nickname, its kind and optionally the last four digits, never a balance or a full number. A commitment can name the one it comes out of, and an expense can record the one that paid it.
_Avoid_: Account, card, funding source

**Category**:
A family's label for grouping spending, such as Groceries or Utilities, with an icon and colour. Every expense has exactly one. An archived category stays on old expenses but can't be picked for new ones.
_Avoid_: Tag, type, bucket

**Utility**:
A metered service for a house, like hydro, gas or water, paid through a commitment whose amount varies. Each payment records the service period and, when the `utility-usage` flag is on, how much was used.
_Avoid_: Utility account

**Income**:
Money the household expects to earn, on a schedule like pay or once like a bonus, optionally tied to the member who earns it (the earner). Only expected income is tracked: actual deposits and bank balances are not recorded.
_Avoid_: Revenue, earnings, balance

**Budget period**:
A calendar month. Budgets are set per period, but spending can be totalled over any date range.
_Avoid_: Cycle, pay period

**Budget allocation**:
The amount a family plans to spend in one category over one budget period.
_Avoid_: Budget line, envelope, limit

**Cash basis**:
Spending counts in the month it was paid, not the month it was due.

## Scheduling

**Task**:
A job to get done for a house or a pet, like draining the hot water tank or giving flea medication. Anything that repeats is a task, including "book Rosie's checkup".
_Avoid_: Event, maintenance item, upkeep, to-do

**Checklist**:
The ordered steps for doing a task, like turning off the gas before draining the tank. Steps are ticked off fresh each time the task is done; the history records only that it was done.
_Avoid_: Subtasks, steps list

**Appointment**:
A single date and time to be somewhere or meet someone, for a house, a pet or a project, optionally with a contact, like Rosie's checkup or a contractor's site visit. Appointments never repeat.
_Avoid_: Event, booking, visit

**Agenda**:
The Dashboard's list of what needs attention soon: due tasks, appointments and occurrences together.
_Avoid_: Event, feed, timeline

**Anchored schedule**:
Repeats on fixed calendar dates regardless of when it was last done, like a bill due on the 5th.

**Floating schedule**:
Repeats a set interval after it was last done or paid, like a furnace filter every three months.

## Records

**Archived**:
Retired from everyday views but kept, with all its history intact. Archiving is how things are removed.
_Avoid_: Deleted, inactive

**Recently deleted**:
Where a deleted expense waits for 30 days, so it can be restored, before it is purged for good. Deleting is for mistakes; things that are retired are archived instead.
_Avoid_: Trash, bin (in UI copy), archive

**Project**:
A one-off piece of work on a house, like replacing the eavestroughs. It is a project, not a task, when it involves a quote, a contract or more than one payment.
_Avoid_: Renovation (as the general term), job

**Quote**:
A contact's price for a project. Accepting one sets the project's contract amount and contractor; the others stay on record.
_Avoid_: Estimate, bid

**Pet**:
An animal the family cares for. Pets belong to the family and can have tasks, appointments, expenses, documents and vets.
_Avoid_: Animal

**Document**:
A file attached to a record: a quote, invoice, receipt, warranty, permit or photo. A newer document can supersede an older one without replacing it.
_Avoid_: Attachment (in UI copy), file

## Contacts

**Contact**:
A person or company that helps the family with its life and home, such as an insurance broker, a realtor, a plumber or a vet. Contacts belong to the family, can be starred as favourites, and each has one contact role. A commitment can name its contact, and an expense can record who it was paid to.
_Avoid_: Vendor, provider, address book entry

**Contact role**:
What a contact does for the family, such as Mortgage broker, Roofer or Vet. A family starts with a default set, which it can add to, rename and archive.
_Avoid_: Trade, type, category

**Contractor**:
The contact hired for a project. It names the part a contact plays, not a separate record.
_Avoid_: Vendor, tradesperson

**Vet**:
The contact a pet sees for care. Each pet has a primary vet and may have an emergency vet. It names the part a contact plays, not a separate record.
_Avoid_: Clinic
