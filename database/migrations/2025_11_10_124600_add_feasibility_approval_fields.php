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
            $table->text('existing_solution')->nullable()->after('alternative_proposal');
            $table->text('off_the_shelf_solution')->nullable()->after('existing_solution');
            $table->text('reject_reason')->nullable()->after('off_the_shelf_solution');
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
                'existing_solution',
                'off_the_shelf_solution',
                'reject_reason',
                'approval_status',
                'approved_at',
            ]);
        });
    }
};
