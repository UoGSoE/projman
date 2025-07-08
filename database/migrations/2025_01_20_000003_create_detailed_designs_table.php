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
            $table->string('deliverable_title');
            $table->foreignId('designed_by')->constrained('users')->nullable();
            $table->string('service_function');
            $table->text('functional_requirements');
            $table->text('non_functional_requirements');
            $table->string('hld_design_link');
            $table->string('approval_delivery');
            $table->string('approval_operations');
            $table->string('approval_resilience');
            $table->string('approval_change_board');
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
