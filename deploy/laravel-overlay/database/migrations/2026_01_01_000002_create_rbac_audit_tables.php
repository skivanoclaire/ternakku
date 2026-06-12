<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC + ABAC + Audit trail untuk aplikasi web TernakKu.
 *  - roles / permissions / pivot  -> RBAC (peran: admin, peternak, pembeli)
 *  - kolom is_active & wilayah_id di users -> atribut untuk ABAC
 *  - audits -> jejak audit siapa-melakukan-apa-kapan (transaksi & perubahan harga)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();          // admin / peternak / pembeli
            $t->string('label')->nullable();
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();           // mis. ternak.update, harga.update, listing.verify
            $t->string('label')->nullable();
            $t->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'user_id']);
        });

        // atribut tambahan di users untuk keputusan ABAC
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('wilayah_id')->nullable();
        });

        Schema::create('audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event');                    // created / updated / deleted / login / harga.update
            $t->string('auditable_type')->nullable();
            $t->unsignedBigInteger('auditable_id')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->string('url')->nullable();
            $t->timestamps();
            $t->index(['auditable_type', 'auditable_id']);
            $t->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['is_active', 'wilayah_id']);
        });
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
