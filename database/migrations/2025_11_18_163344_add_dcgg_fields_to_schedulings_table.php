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
        Schema::table('schedulings', function (Blueprint $table) {
            $table->timestamp('submitted_to_dcgg_at')->nullable()->after('fields_locked');
            $table->foreignId('submitted_to_dcgg_by')->nullable()->constrained('users')->after('submitted_to_dcgg_at');
            $table->timestamp('scheduled_at')->nullable()->after('submitted_to_dcgg_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedulings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_to_dcgg_by');
            $table->dropColumn(['submitted_to_dcgg_at', 'scheduled_at']);
        });
    }
};
