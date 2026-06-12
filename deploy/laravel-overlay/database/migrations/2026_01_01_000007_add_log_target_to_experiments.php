<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Penanda apakah eksperimen memodelkan ln(bobot) (target log). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $t) {
            if (! Schema::hasColumn('experiments', 'log_target')) {
                $t->boolean('log_target')->default(false)->after('scenario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $t) {
            $t->dropColumn('log_target');
        });
    }
};
