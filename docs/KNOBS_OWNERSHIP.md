# `knobs` is owned by chatbot-util

**Status:** this package's migration and model are inert. No data is deleted or
altered. Coordinated with Track B and Track C under
[iquesters/chatbot-util#137](https://github.com/iquesters/chatbot-util/issues/137).

## What was wrong

Two incompatible definitions of one table name.

| | this package | chatbot-util |
|---|---|---|
| identity | `uid` ULID, unique | `integration_uid CHAR(26)` + `knob_type CHAR(64)` |
| status | `string` default `'unknown'` | `ENUM('inactive','active','archived')` |
| uniqueness | `uid` only | `(integration_uid, knob_type, version)` and one active row per `(integration_uid, knob_type)` via a STORED generated column |
| indexes | none beyond the unique | three secondary |
| triggers | none | two `BEFORE` triggers that compute `sha256` when the caller omits it |
| timestamps | nullable | `NOT NULL`, with `ON UPDATE CURRENT_TIMESTAMP` |

Neither is a superset of the other. The deployed shape is chatbot-util's, which
is the one every live reader expects.

## The hazard was the rollback, not the create

`up()` used `Schema::create`, which on an existing table raises SQLSTATE 42S01
"table already exists" — it does not drop anything. Running it against a
populated database failed the migration. Unpleasant, recoverable.

`down()` used `Schema::dropIfExists('knobs')`. `php artisan migrate:rollback`
would have dropped the canonical table — every knob row and both triggers —
from any installation where this migration was recorded as applied. Nothing in
Laravel needed the table, so the loss would have surfaced as chatbot-util
failing rather than as a Laravel error.

Both halves are now no-ops, and `down()` logs a warning naming the owner.

## Nothing in PHP reads or writes it

Audited 2026-11-20 across `dev`, `integration`, `messenger` and
`smart-messenger`:

* No controller, service, command, seeder, job or view touches `knobs`.
* `src/Models/Knob.php` has **zero** references. It is now marked
  `@deprecated`, and kept rather than deleted because removing a public class
  from a published composer package is a breaking change for consumers outside
  this workspace.
* `integration::integrations.knob` is rendered by `IntegrationConfigController`
  with `$knobStatus` hardcoded to `'unknown'` and the knob types as literals.
  The real data is fetched from chatbot-util's HTTP API by JavaScript on the
  page, which is the pattern to keep using.
* The other "knob" matches in the `integration` package are the `gc_vec_knob`
  and `gc_chatbot_knob` **integration meta keys** — rows in
  `integration_metas`, not this table.

So this change removes dead code paths rather than altering behaviour.

## Why the migration file is kept

Installations that already ran it have a row for it in Laravel's `migrations`
ledger. Deleting the file would leave that row pointing at a migration Laravel
cannot find, which breaks `migrate:status` and `migrate:rollback` for the whole
batch.

This matters more than it sounds: **whether this migration is recorded as
applied on Stage or Production has not been established.** It cannot be
inferred from the table's current shape — a row can be present in the ledger
while the table has since been replaced by chatbot-util's `db/3.sql`, which is
exactly what would have happened if Laravel ran first. Keeping the file with
inert `up()`/`down()` leaves every installation consistent either way and needs
no ledger surgery.

## Not done here, and not to be done without approval

* **Do not** run `migrate:rollback` for this migration against a live database.
* **Do not** insert or delete rows in Laravel's `migrations` ledger by hand to
  mark it applied or unapplied.
* **Do not** drop or alter the live `knobs` table from PHP.

Reading Laravel's ledger on Stage and Production is a read-only task that
remains open; it is tracked as `laravel_migration_ledger_unverified` in
chatbot-util's release manifest.

## Fresh-install ordering

On a new environment, chatbot-util's Alembic revision `0001` creates `knobs`
along with the rest of the schema it owns. It also refuses to bootstrap a schema
that already contains `knobs`, so if this package had created the table first,
that migration would block with a confusing error. Leaving creation to the owner
avoids it.

The documented order is Laravel migrations first — chatbot-util has foreign keys
onto `organisations`, `integrations` and `jobs`, and its commerce triggers read
`model_has_organisations` — then Alembic. With `up()` inert, that order works
without either side colliding on `knobs`.
