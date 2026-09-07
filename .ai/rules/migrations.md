---
paths:
  - 'database/migrations/**'
---

# Migrations

## Users table columns live across 3 migrations without duplication
The base users migration already creates phone, avatar, balance and role. Migrations 000002 and 000013 add the remaining is_admin/is_banned/banned_at/banned_reason and drop role. Do NOT re-add columns that already exist (phone/avatar/balance/is_admin) in later migrations or `refresh`/fresh install fails with 'duplicate column'. Keep column additions in one migration only.
