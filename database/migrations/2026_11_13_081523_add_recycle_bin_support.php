<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['testimonials', 'vouchers', 'transactions', 'exam_attempts', 'exam_claims'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
        Schema::create('trash_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('resource');
            $table->unsignedBigInteger('record_id');
            $table->json('items');
            $table->timestamp('created_at');
            $table->unique(['resource', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trash_batches');
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
