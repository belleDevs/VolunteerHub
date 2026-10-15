<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('volunteer_id')->constrained('users')->cascadeOnDelete();
            $table->string('mode')->default('assistant');
            $table->boolean('needs_takeover')->default(false);
            $table->timestamp('taken_over_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'volunteer_id']);
        });

        Schema::table('chatbot_responses', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->boolean('needs_takeover')->default(false)->after('confidence');
            $table->string('answered_by')->default('assistant')->after('needs_takeover');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['needs_takeover', 'answered_by']);
        });

        Schema::dropIfExists('org_conversations');
    }
};
