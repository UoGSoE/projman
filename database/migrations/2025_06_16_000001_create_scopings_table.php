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
        Schema::create('scopings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('assessed_by')->nullable()->constrained('users');
            $table->text('estimated_effort')->nullable();
            $table->text('in_scope')->nullable();
            $table->text('out_of_scope')->nullable();
            $table->text('assumptions')->nullable();
            $table->json('skills_required')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scopings');
    }
};
