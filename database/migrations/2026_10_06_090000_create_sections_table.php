<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Bidang" — kelompok besar konten (TWK, TIU, TKP, dan bidang lain
     * yang/admin yang tambahkan sendiri).
     *
     * Ini data, bukan enum: daftar bidang boleh bertambah, berubah, dan
     * dihapus. Tabel `materials`, `questions`, dan `articles` nanti
     * menunjuk ke sini lewat `section_id`.
     */
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('code', 30)->unique();

            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();

            // Penampilan — kolom ini yang dulu hardcoded di enum.
            $table->string('icon', 40)->nullable();
            $table->string('tone', 20)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Soft delete: bidang yang masih dirujuk materi/soal tidak hilang
            // permanen saat admin salah hapus.
            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
