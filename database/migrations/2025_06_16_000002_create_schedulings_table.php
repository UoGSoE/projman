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
        Schema::create('schedulings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->text('key_skills')->nullable();
            $table->text('cose_it_staff')->nullable();
            $table->date('estimated_start_date')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->date('change_board_date')->nullable();
            $table->foreignId('assigned_to')->constrained('users')->nullable();
            $table->string('priority')->nullable();
            $table->string('team_assignment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedulings');
    }
};
