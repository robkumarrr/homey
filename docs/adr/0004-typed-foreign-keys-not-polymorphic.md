# Links use typed foreign keys, not polymorphic relations

An expense can point at a commitment's occurrence, a project or a pet, and documents attach to several kinds of record. We use a separate nullable foreign key for each target (`commitment_id`, `project_id`, `pet_id`) instead of Laravel's `*_type`/`*_id` morph columns. Morphs avoid a migration when a new target arrives, but the database can't enforce them. Over the app's life there will be about five targets, so permanent referential integrity is worth one migration per new target.

## Consequences

A CHECK constraint allows at most one subject per expense, and a test inserts through raw SQL to prove the constraint still holds after every migration.
