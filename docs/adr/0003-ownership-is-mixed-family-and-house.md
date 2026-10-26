# Some records belong to the family, others to a house

A family can have several houses, and each record belongs to whichever level it naturally lives at. Things that follow the family if it moves are **family-owned**: members, income, commitments, categories, budgets, pets and vets. Things tied to a property are **house-owned**: utilities, tasks for the house, projects and contractors. We considered putting everything under the house, but income, pets and budgets aren't about a property. Putting everything under the family would make "this house's spending" impossible to separate once there's a second house.

## Consequences

Every family-owned table has a `family_id`. Every house-owned table has a `house_id`, even when the owner could be reached by a join. One predicate and one index per table is easier to keep correct than joins.
