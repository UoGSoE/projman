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
            $table->string('deliverable_title');
            $table->text('key_skills');
            $table->text('cose_it_staff')->nullable();
            $table->date('estimated_start_date');
            $table->date('estimated_completion_date');
            $table->date('change_board_date');
            $table->foreignId('assigned_to')->constrained('users');
            $table->string('priority');
            $table->string('team_assignment');
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
