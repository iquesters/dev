<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * `knobs` is owned by chatbot-util, not by this package.
 *
 * WHY THIS MIGRATION NO LONGER CREATES OR DROPS THE TABLE
 * -------------------------------------------------------
 * Two incompatible definitions of `knobs` existed, sharing only the name.
 *
 *   this package   id, uid (ULID, unique), version, knob, sha256,
 *                  status (string, default 'unknown'), created_by, updated_by,
 *                  timestamps
 *
 *   chatbot-util   id, integration_uid CHAR(26), knob_type CHAR(64), version,
 *                  knob, sha256, status ENUM('inactive','active','archived'),
 *                  a STORED generated column `active_unique` enforcing one
 *                  active row per (integration_uid, knob_type), two unique
 *                  keys, three secondary indexes, and two BEFORE triggers that
 *                  compute sha256 when the caller omits it
 *
 * Neither is a superset of the other, and the `uid` column this package's
 * model declares as fillable does not exist in the live table at all. The
 * deployed shape is chatbot-util's, which is the one every live reader
 * expects.
 *
 * chatbot-util has therefore been declared the canonical owner, and the table
 * is carried by its Alembic revision 0001. See
 * iquesters/chatbot-util#137 and the `ownership_conflicts` entry in
 * chatbot-util/db/migrations/releases/001-baseline/manifest.yaml.
 *
 * THE ACTUAL HAZARD WAS `down()`, NOT `up()`
 * ------------------------------------------
 * `up()` used `Schema::create`, which on an existing table raises SQLSTATE
 * 42S01 "table already exists". Running it against a populated database
 * therefore failed the migration; it did not silently destroy anything. That
 * is unpleasant but recoverable.
 *
 * `down()` used `Schema::dropIfExists('knobs')`, and that is the dangerous
 * one: `php artisan migrate:rollback` would have dropped the canonical table,
 * with every knob row and both triggers, from any installation where this
 * migration was recorded as applied. Nothing in Laravel needed the table, so
 * the loss would have been discovered by chatbot-util failing, not by Laravel.
 *
 * Both are now no-ops. A rollback of this migration can no longer destroy the
 * table, and a fresh `migrate` can no longer create a shape that chatbot-util
 * cannot read.
 *
 * WHY THE FILE IS KEPT INSTEAD OF DELETED
 * ---------------------------------------
 * Installations that already recorded this migration have a row for it in
 * Laravel's `migrations` ledger. Deleting the file would leave that row
 * pointing at a migration Laravel can no longer find, which breaks
 * `migrate:status` and `migrate:rollback` for the whole batch. Keeping the
 * file with inert up()/down() leaves every existing installation consistent
 * and needs no ledger surgery - which matters, because whether this migration
 * is recorded as applied on Stage or Production has not been established and
 * cannot be inferred from the table's current shape.
 *
 * NOTHING IN PHP READS OR WRITES THIS TABLE
 * -----------------------------------------
 * Audited on 2026-11-20 across the dev, integration, messenger and
 * smart-messenger packages: no controller, service, command, seeder, job or
 * view touches `knobs`. This package ships `src/Models/Knob.php`, but nothing
 * references it. The one Laravel page about knobs,
 * `integration::integrations.knob`, is rendered by `IntegrationConfigController`
 * with `$knobStatus` hardcoded to 'unknown' and the knob types as literals; the
 * real data is fetched from chatbot-util's HTTP API by JavaScript on the page.
 * The other "knob" matches in the integration package are the `gc_vec_knob`
 * and `gc_chatbot_knob` *integration meta keys*, which are rows in
 * `integration_metas`, not this table.
 *
 * So this change removes dead code paths. It does not alter any behaviour that
 * anything depends on.
 *
 * NO DATA IS DELETED OR ALTERED BY THIS CHANGE.
 */
return new class extends Migration
{
    /**
     * Intentionally inert.
     *
     * Creating the table here would either collide with the canonical one or,
     * on a fresh install, produce a shape chatbot-util cannot read - and
     * chatbot-util's own revision 0001 refuses to bootstrap a schema that
     * already contains `knobs`, so a Laravel-first install would block its
     * migration with a confusing error. Leaving creation to the owner avoids
     * both.
     */
    public function up(): void
    {
        if (Schema::hasTable('knobs')) {
            Log::info('knobs migration skipped: the table exists and is owned '
                . 'by chatbot-util (Alembic revision 0001).');
            return;
        }

        Log::info('knobs migration skipped: the table is owned by chatbot-util '
            . '(Alembic revision 0001) and is created by its migration, not '
            . 'this one.');
    }

    /**
     * Intentionally inert, and this is the important half.
     *
     * The previous implementation was `Schema::dropIfExists('knobs')`. A
     * rollback would have dropped the canonical table and its data from any
     * installation that recorded this migration. A migration must not be able
     * to destroy a table it does not own.
     */
    public function down(): void
    {
        Log::warning('knobs rollback skipped: the table is owned by '
            . 'chatbot-util (Alembic revision 0001). Dropping it here would '
            . 'destroy live knob data this package does not own and nothing in '
            . 'PHP reads. To remove it deliberately, do so through '
            . 'chatbot-util under its own review.');
    }
};
