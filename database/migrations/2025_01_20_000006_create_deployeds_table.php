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
            $table->string('deliverable_title');
            $table->foreignId('deployed_by')->constrained('users')->nullable();
            $table->string('environment');
            $table->string('status');
            $table->date('deployment_date');
            $table->string('version');
            $table->string('production_url');
            $table->text('deployment_notes')->nullable();
            $table->text('rollback_plan')->nullable();
            $table->text('monitoring_notes')->nullable();
            $table->string('deployment_sign_off');
            $table->string('operations_sign_off');
            $table->string('user_acceptance');
            $table->string('service_delivery_sign_off');
            $table->string('change_advisory_sign_off');
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
