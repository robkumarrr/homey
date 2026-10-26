# Membership is a family_user pivot with a role

Members belong to a family through a `family_user` pivot with a `role` column, not a `users.family_id` column, even though each person belongs to one family today. The pivot is the shape that joining another family or multiple roles would need. It also leaves the `users` table alone, which matters because Fortify's passkeys, two-factor and sessions depend on it.
