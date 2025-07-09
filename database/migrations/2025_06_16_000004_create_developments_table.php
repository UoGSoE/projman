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
        Schema::create('developments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('deliverable_title');
            $table->foreignId('lead_developer')->constrained('users')->nullable();
            $table->string('development_team');
            $table->text('technical_approach');
            $table->text('development_notes');
            $table->string('repository_link');
            $table->string('status');
            $table->date('start_date');
            $table->date('completion_date');
            $table->text('code_review_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developments');
    }
};
