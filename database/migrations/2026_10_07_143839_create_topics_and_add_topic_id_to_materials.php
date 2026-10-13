<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['section_id', 'name']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->constrained()->restrictOnDelete();
        });

        // Include archived materials so restoring them preserves their placement.
        foreach (DB::table('materials')->select('section_id')->distinct()->get() as $row) {
            $topicId = DB::table('topics')->insertGetId([
                'section_id' => $row->section_id,
                'name' => 'Umum',
                'position' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('materials')->where('section_id', $row->section_id)->update(['topic_id' => $topicId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topic_id');
        });
        Schema::dropIfExists('topics');
    }
};
