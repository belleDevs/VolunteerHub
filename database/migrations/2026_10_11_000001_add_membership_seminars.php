<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_seminars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('location');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('fee_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('membership_fee_paid_at')->nullable()->after('availability');
            $table->foreignId('membership_seminar_id')->nullable()->after('membership_fee_paid_at')->constrained('membership_seminars')->nullOnDelete();
            $table->timestamp('membership_attended_at')->nullable()->after('membership_seminar_id');
        });

        DB::table('users')
            ->where('role', 'volunteer')
            ->where('status', 'approved')
            ->whereNotNull('organization_id')
            ->update([
                'membership_fee_paid_at' => DB::raw('created_at'),
                'membership_attended_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_seminar_id');
            $table->dropColumn(['membership_fee_paid_at', 'membership_attended_at']);
        });

        Schema::dropIfExists('membership_seminars');
    }
};
