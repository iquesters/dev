<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        Schema::table('vector_responses', function (Blueprint $table) {
            try {
                $table->dropUnique('vector_responses_operation_id_unique');
            } catch (\Throwable) {
                // Unique index may not exist on fresh databases.
            }

            try {
                $table->index('operation_id', 'vector_responses_operation_id_index');
            } catch (\Throwable) {
                // Ignore if the normal index already exists.
            }
        });
    }

    public function down(): void
    {
        Schema::table('vector_responses', function (Blueprint $table) {
            try {
                $table->dropIndex('vector_responses_operation_id_index');
            } catch (\Throwable) {
                // Ignore missing index.
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
