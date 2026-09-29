<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE assignments MODIFY status ENUM('pending', 'approved', 'submitted', 'rejected', 'cancelled', 'completed') DEFAULT 'pending'");

        Schema::table('assignments', function (Blueprint $table) {
            $table->text('completion_note')->nullable()->after('feedback');
            $table->string('completion_proof_path')->nullable()->after('completion_note');
            $table->timestamp('submitted_at')->nullable()->after('completion_proof_path');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'completion_note',
                'completion_proof_path',
                'submitted_at',
                'reviewed_at',
            ]);
        });

        DB::statement("ALTER TABLE assignments MODIFY status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending'");
    }
};
