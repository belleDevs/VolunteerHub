<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");

        DB::table('tasks')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('assignments')
                    ->whereColumn('assignments.task_id', 'tasks.id')
                    ->whereNull('assignments.deleted_at')
                    ->where('assignments.status', 'approved');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('assignments')
                    ->whereColumn('assignments.task_id', 'tasks.id')
                    ->whereNull('assignments.deleted_at')
                    ->whereIn('assignments.status', ['submitted', 'completed']);
            })
            ->update(['status' => 'assigned']);
    }

    public function down(): void
    {
        DB::table('tasks')->where('status', 'assigned')->update(['status' => 'in_progress']);

        DB::statement("ALTER TABLE tasks MODIFY status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
