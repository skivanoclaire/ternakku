<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peternak mengisi ras sebagai teks bebas (mis. "Bali", "Limousin"),
 * sementara ras_id (FK ref_ras) untuk referensi terstruktur menyusul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ternak', function (Blueprint $t) {
            if (! Schema::hasColumn('ternak', 'ras')) {
                $t->string('ras')->nullable()->after('ras_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ternak', function (Blueprint $t) {
            $t->dropColumn('ras');
        });
    }
};
