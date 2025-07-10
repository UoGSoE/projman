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
            $table->foreignId('deployed_by')->nullable()->constrained('users');
            $table->string('environment')->nullable();
            $table->string('status')->nullable();
            $table->date('deployment_date')->nullable();
            $table->string('version')->nullable();
            $table->string('production_url')->nullable();
            $table->text('deployment_notes')->nullable();
            $table->text('rollback_plan')->nullable();
            $table->text('monitoring_notes')->nullable();
            $table->string('deployment_sign_off')->nullable();
            $table->string('operations_sign_off')->nullable();
            $table->string('user_acceptance')->nullable();
            $table->string('service_delivery_sign_off')->nullable();
            $table->string('change_advisory_sign_off')->nullable();
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
