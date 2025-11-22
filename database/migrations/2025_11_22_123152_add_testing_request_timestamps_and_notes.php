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
            // Add request timestamps
            $table->timestamp('uat_requested_at')->nullable()->after('uat_tester_id');
            $table->timestamp('service_acceptance_requested_at')->nullable()->after('service_accepted_at');

            // Add note fields for sign-off explanations
            $table->text('testing_sign_off_notes')->nullable()->after('testing_sign_off');
            $table->text('user_acceptance_notes')->nullable()->after('user_acceptance');
            $table->text('testing_lead_sign_off_notes')->nullable()->after('testing_lead_sign_off');
            $table->text('service_delivery_sign_off_notes')->nullable()->after('service_delivery_sign_off');
            $table->text('service_resilience_sign_off_notes')->nullable()->after('service_resilience_sign_off');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testings', function (Blueprint $table) {
            $table->dropColumn([
                'uat_requested_at',
                'service_acceptance_requested_at',
                'testing_sign_off_notes',
                'user_acceptance_notes',
                'testing_lead_sign_off_notes',
                'service_delivery_sign_off_notes',
                'service_resilience_sign_off_notes',
            ]);
        });
    }
};
