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
        Schema::create('deployeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');

            // Deployment Lead & Service Info
            $table->foreignId('deployment_lead_id')->nullable()->constrained('users');
            $table->string('service_function')->nullable();
            $table->text('system')->nullable();

            // Live Functional Testing
            $table->text('fr1')->nullable();
            $table->text('fr2')->nullable();
            $table->text('fr3')->nullable();

            // Live Non-Functional Testing
            $table->text('nfr1')->nullable();
            $table->text('nfr2')->nullable();
            $table->text('nfr3')->nullable();

            // BAU / Operational
            $table->text('bau_operational_wiki')->nullable();

            // Service Handover - Service Resilience
            $table->string('service_resilience_approval')->default('pending');
            $table->text('service_resilience_notes')->nullable();

            // Service Handover - Service Operations
            $table->string('service_operations_approval')->default('pending');
            $table->text('service_operations_notes')->nullable();

            // Service Handover - Service Delivery
            $table->string('service_delivery_approval')->default('pending');
            $table->text('service_delivery_notes')->nullable();

            // Workflow timestamps
            $table->timestamp('service_accepted_at')->nullable();
            $table->timestamp('deployment_approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployeds');
    }
};
