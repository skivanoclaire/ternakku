<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skenario sumber data eksperimen: B (nyata->nyata), A (sintetis->nyata),
 * C (gabungan->nyata). Memungkinkan validasi data sintetis di leaderboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $t) {
            if (! Schema::hasColumn('experiments', 'scenario')) {
                $t->string('scenario', 1)->default('B')->after('eval_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $t) {
            $t->dropColumn('scenario');
        });
    }
};
