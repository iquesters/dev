<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vector_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('vector_responses', 'operation_id')) {
                $table->unsignedBigInteger('operation_id')->after('job_uuid');
            }

            if (!Schema::hasColumn('vector_responses', 'message')) {
                $table->text('message')->nullable()->after('operation_id');
            }

            if (!Schema::hasColumn('vector_responses', 'step_status')) {
                $table->integer('step_status')->nullable()->after('message');
            }
        });

        $hasUniqueIndex = collect(DB::select("SHOW INDEX FROM `vector_responses` WHERE Key_name = 'vector_responses_operation_id_unique'"))
            ->isNotEmpty();

        $hasNormalIndex = collect(DB::select("SHOW INDEX FROM `vector_responses` WHERE Key_name = 'vector_responses_operation_id_index'"))
            ->isNotEmpty();

        Schema::table('vector_responses', function (Blueprint $table) use ($hasUniqueIndex, $hasNormalIndex) {
            if ($hasUniqueIndex) {
                $table->dropUnique('vector_responses_operation_id_unique');
            }

            if (! $hasNormalIndex) {
                $table->index('operation_id', 'vector_responses_operation_id_index');
            }
        });
    }

    public function down(): void
    {
        $hasNormalIndex = collect(DB::select("SHOW INDEX FROM `vector_responses` WHERE Key_name = 'vector_responses_operation_id_index'"))
            ->isNotEmpty();

        Schema::table('vector_responses', function (Blueprint $table) use ($hasNormalIndex) {
            if ($hasNormalIndex) {
                $table->dropIndex('vector_responses_operation_id_index');
            }

            if (Schema::hasColumn('vector_responses', 'step_status')) {
                $table->dropColumn('step_status');
            }

            if (Schema::hasColumn('vector_responses', 'message')) {
                $table->dropColumn('message');
            }

            if (Schema::hasColumn('vector_responses', 'operation_id')) {
                $table->dropColumn('operation_id');
            }
        });
    }
};
