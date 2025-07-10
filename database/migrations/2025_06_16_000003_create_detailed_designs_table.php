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
        Schema::create('detailed_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('designed_by')->nullable()->constrained('users');
            $table->string('service_function')->nullable();
            $table->text('functional_requirements')->nullable();
            $table->text('non_functional_requirements')->nullable();
            $table->string('hld_design_link')->nullable();
            $table->string('approval_delivery')->nullable();
            $table->string('approval_operations')->nullable();
            $table->string('approval_resilience')->nullable();
            $table->string('approval_change_board')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detailed_designs');
    }
};
