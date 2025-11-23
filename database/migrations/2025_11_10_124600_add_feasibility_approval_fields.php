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
        Schema::table('feasibilities', function (Blueprint $table) {
            $table->string('existing_solution_status')->nullable()->after('alternative_proposal');
            $table->text('existing_solution_notes')->nullable()->after('existing_solution_status');
            $table->string('off_the_shelf_solution_status')->nullable()->after('existing_solution_notes');
            $table->text('off_the_shelf_solution_notes')->nullable()->after('off_the_shelf_solution_status');
            $table->text('reject_reason')->nullable()->after('off_the_shelf_solution_notes');
            $table->string('approval_status')->default('pending')->after('reject_reason');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('actioned_by')->nullable()->constrained('users')->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feasibilities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actioned_by');
            $table->dropColumn([
                'existing_solution_status',
                'existing_solution_notes',
                'off_the_shelf_solution_status',
                'off_the_shelf_solution_notes',
                'reject_reason',
                'approval_status',
                'approved_at',
            ]);
        });
    }
};
