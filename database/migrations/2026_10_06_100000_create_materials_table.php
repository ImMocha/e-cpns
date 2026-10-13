<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Materi pembelajaran (§10): PDF dan Video.
     *
     * Setiap materi belong ke satu `section` (bidang) — bukan pivot, supaya
     * satu materi tidak muncul di dua bidang dan filter peserta tidak ambigu.
     *
     * Kolom sumber sengaja terpisah (`file_path` untuk PDF, `video_url` untuk
     * video) alih-alih satu kolom generik, karena keduanya divalidasi dan
     * ditampilkan berbeda. Sumber video masih TBD (§44 TBD-11).
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->text('summary')->nullable();

            // pdf | video
            $table->string('type', 20)->default('pdf')->index();

            // free | premium — §38 Access Control
            $table->string('access_level', 20)->default('free')->index();

            // draft | published
            $table->string('status', 20)->default('draft')->index();

            // Sumber konten. Tepat satu yang terisi, sesuai `type`.
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // dalam byte
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Soft delete: materi yang salah terbit masih bisa dikembalikan.
            $table->softDeletes();

            $table->timestamps();

            $table->index(['section_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
