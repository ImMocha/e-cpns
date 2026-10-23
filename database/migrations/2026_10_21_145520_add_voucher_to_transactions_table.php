<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('voucher_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('voucher_code', 40)->nullable();
            $table->unsignedInteger('discount_amount')->default(0);
            $table->index(['voucher_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['voucher_id']);
            $table->dropIndex(['voucher_id', 'status']);
            $table->dropColumn(['voucher_id', 'voucher_code', 'discount_amount']);
        });
    }
};
