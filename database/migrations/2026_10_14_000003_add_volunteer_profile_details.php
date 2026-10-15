<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY availability VARCHAR(32) NOT NULL DEFAULT 'active'");
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'availability_notes')) {
                $table->text('availability_notes')->nullable();
            }
            if (!Schema::hasColumn('users', 'availability_schedule')) {
                $table->json('availability_schedule')->nullable();
            }
            if (!Schema::hasColumn('users', 'interests')) {
                $table->text('interests')->nullable();
            }
            if (!Schema::hasColumn('users', 'volunteer_experience')) {
                $table->text('volunteer_experience')->nullable();
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'responded_at')) {
                $table->timestamp('responded_at')->nullable();
            }
        });

        Schema::table('chatbot_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_responses', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['availability_notes', 'availability_schedule', 'interests', 'volunteer_experience'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'responded_at')) {
                $table->dropColumn('responded_at');
            }
        });

        Schema::table('chatbot_responses', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_responses', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
};
