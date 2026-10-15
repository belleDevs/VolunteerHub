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
        Schema::table('users', function (Blueprint $table) {
            $table->string('event_reward')->nullable()->after('organization_type');
            $table->string('task_reward')->nullable()->after('event_reward');
        });

        DB::table('users')
            ->where('role', 'organization')
            ->whereNull('event_reward')
            ->update([
                'event_reward' => 'service_certificate',
                'task_reward' => 'service_hours',
            ]);

        $choices = [
            'org@volunteerhub.ph' => ['service_certificate', 'service_hours'],
            'info@surigaoyouthbuilders.org' => ['public_recognition', 'skill_badge'],
            'contact@mindanaorelief.org' => ['priority_invite', 'allowance_note'],
        ];

        foreach ($choices as $email => [$eventReward, $taskReward]) {
            DB::table('users')->where('email', $email)->update([
                'event_reward' => $eventReward,
                'task_reward' => $taskReward,
            ]);
        }

        Schema::create('volunteer_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->enum('reward_scope', ['event', 'task']);
            $table->string('reward_type');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['user_id', 'reward_scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_rewards');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['event_reward', 'task_reward']);
        });
    }
};
