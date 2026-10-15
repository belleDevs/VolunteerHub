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
            $table->string('organization_type')->nullable()->after('bio');
        });

        $existing = [
            'org@volunteerhub.ph' => 'women_empowerment',
            'info@surigaoyouthbuilders.org' => 'community_infrastructure',
            'contact@mindanaorelief.org' => 'disaster_response',
        ];

        foreach ($existing as $email => $type) {
            DB::table('users')->where('email', $email)->update(['organization_type' => $type]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('organization_type');
        });
    }
};
