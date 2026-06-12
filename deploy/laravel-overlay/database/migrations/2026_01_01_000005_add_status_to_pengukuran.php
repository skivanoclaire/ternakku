<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status pengukuran: data peternak/impor publik bisa pending/approved/rejected.
 * Data peternak (estimasi) default 'approved'; data latih impor divalidasi researcher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengukuran', function (Blueprint $t) {
            if (! Schema::hasColumn('pengukuran', 'status')) {
                $t->string('status')->default('approved')->after('source');
                $t->index('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengukuran', function (Blueprint $t) {
            $t->dropColumn('status');
        });
    }
};
