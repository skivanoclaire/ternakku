<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel experiment — satu baris per pelatihan model via web (researcher).
 * Menyimpan konfigurasi + metrik + diagnostik agar leaderboard adil & reproducible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('method');                 // schoorl|loglog|linear|rf|xgb
            $t->string('method_label')->nullable();
            $t->json('features');                  // subset fitur
            $t->string('eval_mode')->default('acak');  // acak | lintas
            $t->integer('n_rows')->default(0);
            // metrik
            $t->float('mape')->nullable();
            $t->float('mae')->nullable();
            $t->float('rmse')->nullable();
            $t->float('r2')->nullable();
            $t->float('bias')->nullable();
            $t->float('coverage')->nullable();
            $t->float('interval_kg')->nullable();
            // artefak & diagnostik
            $t->string('model_ver')->nullable();
            $t->json('importance')->nullable();
            $t->json('diagnostics')->nullable();   // titik pred/aktual/residual untuk grafik
            $t->boolean('is_active')->default(false);  // model yang melayani peternak
            $t->timestamps();
            $t->index('mape');
            $t->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
