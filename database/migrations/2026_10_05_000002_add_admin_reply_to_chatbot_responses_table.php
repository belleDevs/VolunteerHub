<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chatbot_responses', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('response');
            $table->foreignId('admin_replied_by')->nullable()->after('admin_reply')->constrained('users')->nullOnDelete();
            $table->timestamp('admin_replied_at')->nullable()->after('admin_replied_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_replied_by');
            $table->dropColumn(['admin_reply', 'admin_replied_at']);
        });
    }
};
