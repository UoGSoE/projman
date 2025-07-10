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
            $table->foreignId('test_lead')->nullable()->constrained('users');
            $table->string('service_function')->nullable();
            $table->string('functional_testing_title')->nullable();
            $table->text('functional_tests')->nullable();
            $table->string('non_functional_testing_title')->nullable();
            $table->text('non_functional_tests')->nullable();
            $table->string('test_repository')->nullable();
            $table->string('testing_sign_off')->nullable();
            $table->string('user_acceptance')->nullable();
            $table->string('testing_lead_sign_off')->nullable();
            $table->string('service_delivery_sign_off')->nullable();
            $table->string('service_resilience_sign_off')->nullable();
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
