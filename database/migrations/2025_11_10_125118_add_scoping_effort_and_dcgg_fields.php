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
        Schema::table('scopings', function (Blueprint $table) {
            $table->string('estimated_effort')->nullable()->change();
            $table->string('dcgg_status')->default('pending')->after('skills_required');
            $table->timestamp('submitted_to_dcgg_at')->nullable()->after('dcgg_status');
            $table->timestamp('scheduled_at')->nullable()->after('submitted_to_dcgg_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scopings', function (Blueprint $table) {
            $table->text('estimated_effort')->nullable()->change();
            $table->dropColumn([
                'dcgg_status',
                'submitted_to_dcgg_at',
                'scheduled_at',
            ]);
        });
    }
};
