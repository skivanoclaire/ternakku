<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel inti TernakKu untuk MVP Modul 1 (estimasi bobot).
 * Salin ke database/migrations/ proyek Laravel, lalu `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_ras', function (Blueprint $t) {
            $t->id();
            $t->string('nama')->unique();       // Hereford, Horqin, Bali, Aceh, ...
            $t->string('jenis')->default('sapi');
            $t->timestamps();
        });

        Schema::create('ternak', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('ras_id')->nullable()->constrained('ref_ras')->nullOnDelete();
            $t->unsignedBigInteger('wilayah_id')->nullable();   // atribut ABAC
            $t->string('jenis')->default('sapi');         // sapi / kambing
            $t->string('kelamin')->nullable();            // jantan / betina
            $t->integer('umur_estimasi_bulan')->nullable();
            $t->decimal('bcs', 3, 1)->nullable();         // body condition score
            $t->timestamps();
        });

        Schema::create('pengukuran', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ternak_id')->nullable()->constrained('ternak')->cascadeOnDelete();
            $t->date('tanggal')->nullable();
            $t->decimal('lingkar_dada_cm', 6, 2)->nullable();
            $t->decimal('panjang_badan_cm', 6, 2)->nullable();
            $t->decimal('tinggi_gumba_cm', 6, 2)->nullable();
            $t->decimal('bobot_timbang_kg', 7, 2)->nullable();   // ground truth bila ada timbangan
            $t->decimal('bobot_estimasi_kg', 7, 2)->nullable();
            $t->string('model_ver')->nullable();                 // versi model saat estimasi
            $t->string('source')->default('farmer');             // public / farmer / synthetic
            $t->string('src_dataset')->nullable();               // hereford / horqin / ...
            $t->string('src_animal_id')->nullable();
            $t->timestamps();
            $t->index(['src_dataset', 'source']);
        });

        Schema::create('model_version', function (Blueprint $t) {
            $t->id();
            $t->string('modul')->default('modul1');     // modul1 / modul2 / ...
            $t->string('versi');                        // mis. modul1-rf-seed42
            $t->json('metrik')->nullable();             // isi metrics.json
            $t->timestamp('dilatih_pada')->nullable();
            $t->timestamps();
        });

        Schema::create('dataset_source', function (Blueprint $t) {
            $t->id();
            $t->string('src_dataset')->unique();
            $t->string('nama');
            $t->integer('n_ekor')->default(0);
            $t->string('lisensi')->nullable();
            $t->text('sitasi')->nullable();
            $t->string('url')->nullable();
            $t->string('source')->default('public');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_source');
        Schema::dropIfExists('model_version');
        Schema::dropIfExists('pengukuran');
        Schema::dropIfExists('ternak');
        Schema::dropIfExists('ref_ras');
    }
};
