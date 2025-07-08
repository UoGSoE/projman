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
        Schema::create('feasibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('assessed_by')->constrained('users')->nullable();
            $table->date('date_assessed')->nullable();
            $table->text('technical_credence')->nullable();
            $table->text('cost_benefit_case')->nullable();
            $table->text('dependencies_prerequisites')->nullable();
            $table->boolean('deadlines_achievable')->default(false);
            $table->text('alternative_proposal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feasibilities');
    }
};
