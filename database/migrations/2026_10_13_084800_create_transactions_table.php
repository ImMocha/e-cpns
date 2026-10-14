<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id', 50)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_package_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('provider', 20)->default('midtrans');
            $table->boolean('is_production')->default(false);
            $table->unsignedInteger('amount');
            $table->json('package_snapshot');
            $table->string('status', 30)->default('pending');
            $table->string('provider_reference', 100)->nullable();
            $table->string('provider_status', 40)->nullable();
            $table->string('payment_type', 40)->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'package_id', 'status']);
            $table->index(['status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
