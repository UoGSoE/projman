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
        Schema::create('testings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('deliverable_title');
            $table->foreignId('test_lead')->constrained('users')->nullable();
            $table->string('service_function');
            $table->string('functional_testing_title');
            $table->text('functional_tests');
            $table->string('non_functional_testing_title');
            $table->text('non_functional_tests');
            $table->string('test_repository');
            $table->string('testing_sign_off');
            $table->string('user_acceptance');
            $table->string('testing_lead_sign_off');
            $table->string('service_delivery_sign_off');
            $table->string('service_resilience_sign_off');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testings');
    }
};
