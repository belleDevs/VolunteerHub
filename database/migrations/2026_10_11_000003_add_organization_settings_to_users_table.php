<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('representative_name')->nullable()->after('phone');
            $table->string('contact_email')->nullable()->after('representative_name');
            $table->string('address', 500)->nullable()->after('contact_email');
            $table->string('website')->nullable()->after('address');
            $table->string('facebook_url')->nullable()->after('website');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->json('notification_preferences')->nullable()->after('instagram_url');
            $table->string('timezone', 64)->nullable()->after('notification_preferences');
            $table->string('date_format', 32)->nullable()->after('timezone');
            $table->string('verification_status', 40)->nullable()->after('date_format');
            $table->text('verification_feedback')->nullable()->after('verification_status');
            $table->text('documents_requested')->nullable()->after('verification_feedback');
            $table->timestamp('verification_updated_at')->nullable()->after('documents_requested');
        });

        DB::table('users')->where('role', 'organization')->where('status', 'approved')->update([
            'verification_status' => 'verified',
            'verification_updated_at' => DB::raw('updated_at'),
        ]);
        DB::table('users')->where('role', 'organization')->where('status', 'pending')->update([
            'verification_status' => 'pending_review',
            'verification_updated_at' => DB::raw('updated_at'),
        ]);
        DB::table('users')->where('role', 'organization')->where('status', 'rejected')->update([
            'verification_status' => 'rejected',
            'verification_updated_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'representative_name',
                'contact_email',
                'address',
                'website',
                'facebook_url',
                'instagram_url',
                'notification_preferences',
                'timezone',
                'date_format',
                'verification_status',
                'verification_feedback',
                'documents_requested',
                'verification_updated_at',
            ]);
        });
    }
};
