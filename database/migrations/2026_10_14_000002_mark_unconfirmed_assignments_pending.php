<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assignments')
            ->where('status', 'approved')
            ->whereNull('submitted_at')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('task_applications')
                    ->whereColumn('task_applications.user_id', 'assignments.user_id')
                    ->whereColumn('task_applications.task_id', 'assignments.task_id')
                    ->where('task_applications.status', 'approved');
            })
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        DB::table('assignments')
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }
};
