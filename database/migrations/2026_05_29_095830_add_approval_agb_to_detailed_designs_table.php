<?php

use App\Models\DetailedDesign;
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
        Schema::table('detailed_designs', function (Blueprint $table) {
            $table->string('approval_agb')->nullable()->after('approval_change_board');
        });

        DetailedDesign::query()->each(function (DetailedDesign $design) {
            $design->approval_agb = $design->approval_change_board;
            $design->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detailed_designs', function (Blueprint $table) {
            $table->dropColumn('approval_agb');
        });
    }
};
