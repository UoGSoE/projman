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
            $table->foreignId('technical_lead_id')->nullable()->constrained('users')->after('assigned_to');
            $table->foreignId('change_champion_id')->nullable()->constrained('users')->after('technical_lead_id');
            $table->string('change_board_outcome')->nullable()->after('change_champion_id');
            $table->boolean('fields_locked')->default(false)->after('change_board_outcome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedulings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('technical_lead_id');
            $table->dropConstrainedForeignId('change_champion_id');
            $table->dropColumn([
                'change_board_outcome',
                'fields_locked',
            ]);
        });
    }
};
