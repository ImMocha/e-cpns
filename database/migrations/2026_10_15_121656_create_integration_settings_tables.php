<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table): void {
            $table->string('key', 30)->primary();
            $table->text('settings')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('integration_setting_audits', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 30);
            $table->string('action', 30);
            $table->json('changed_fields');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
        });

        foreach (['midtrans', 'mail'] as $key) {
            DB::table('integration_settings')->insert([
                'key' => $key, 'version' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_setting_audits');
        Schema::dropIfExists('integration_settings');
    }
};
