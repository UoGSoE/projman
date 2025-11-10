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
        Schema::table('testings', function (Blueprint $table) {
            $table->foreignId('uat_tester_id')->nullable()->constrained('users')->after('test_lead');
            $table->string('uat_approval_status')->default('pending')->after('service_resilience_sign_off');
            $table->timestamp('uat_approved_at')->nullable()->after('uat_approval_status');
            $table->string('service_acceptance_status')->default('pending')->after('uat_approved_at');
            $table->timestamp('service_accepted_at')->nullable()->after('service_acceptance_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uat_tester_id');
            $table->dropColumn([
                'uat_approval_status',
                'uat_approved_at',
                'service_acceptance_status',
                'service_accepted_at',
            ]);
        });
    }
};
