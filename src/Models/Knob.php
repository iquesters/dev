<?php

namespace Iquesters\Dev\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @deprecated The `knobs` table is owned by chatbot-util, not by this package.
 *
 * This model is NOT used anywhere. Audited on 2026-11-20 across the dev,
 * integration, messenger and smart-messenger packages: no controller, service,
 * command, seeder, job or view references it. The one Laravel page about knobs
 * renders with its status hardcoded and fetches the real data from
 * chatbot-util's HTTP API in JavaScript.
 *
 * It is also wrong about the live schema. `$fillable` names `uid`, a column the
 * deployed table does not have, and omits `integration_uid` and `knob_type`,
 * which are NOT NULL there - so `Knob::create([...])` against the real database
 * would fail on both counts. The two unique keys and the generated
 * `active_unique` column that enforce "one active knob per integration and
 * type" are invisible to it as well.
 *
 * Reading or writing knobs from PHP should go through chatbot-util's API, which
 * is what the existing UI already does. The class is kept rather than deleted
 * because removing a public class from a published composer package is a
 * breaking change for any consumer outside this workspace; it is marked
 * deprecated so nothing new is built on it.
 *
 * See iquesters/chatbot-util#137.
 */
class Knob extends Model
{
    use HasFactory;

    protected $table = 'knobs';

    /**
     * @deprecated Does not match the deployed schema. See the class docblock.
     */
    protected $fillable = [
        'uid',
        'version',
        'knob',
        'sha256',
        'status',
        'created_by',
        'updated_by',
    ];
}