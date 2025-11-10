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
        Schema::table('deployeds', function (Blueprint $table) {
            $table->string('service_function')->nullable()->after('deployed_by');
            $table->string('service_acceptance_status')->default('pending')->after('change_advisory_sign_off');
            $table->timestamp('service_accepted_at')->nullable()->after('service_acceptance_status');
            $table->string('deployment_approved_status')->default('pending')->after('service_accepted_at');
            $table->timestamp('deployment_approved_at')->nullable()->after('deployment_approved_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployeds', function (Blueprint $table) {
            $table->dropColumn([
                'service_function',
                'service_acceptance_status',
                'service_accepted_at',
                'deployment_approved_status',
                'deployment_approved_at',
            ]);
        });
    }
};
