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
            $table->foreignId('lead_developer')->nullable()->constrained('users');
            $table->string('development_team')->nullable();
            $table->text('technical_approach')->nullable();
            $table->text('development_notes')->nullable();
            $table->string('repository_link')->nullable();
            $table->string('status')->nullable();
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
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
