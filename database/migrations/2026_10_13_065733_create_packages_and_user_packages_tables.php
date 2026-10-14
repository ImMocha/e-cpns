<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedInteger('tryout_quota')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('material_package', function (Blueprint $table): void {
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->primary(['package_id', 'material_id']);
        });
        Schema::create('exam_package', function (Blueprint $table): void {
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->primary(['package_id', 'exam_id']);
        });
        Schema::create('user_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->string('name', 180);
            $table->unsignedInteger('price');
            $table->unsignedInteger('tryout_quota')->nullable();
            $table->unsignedInteger('tryouts_used')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at', 'expires_at']);
        });
        Schema::create('material_user_package', function (Blueprint $table): void {
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_package_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_package_id', 'material_id']);
        });
        Schema::create('exam_user_package', function (Blueprint $table): void {
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_package_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_package_id', 'exam_id']);
        });
        Schema::table('exams', function (Blueprint $table): void {
            $table->string('access_level', 20)->default('free');
        });
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->foreignId('user_package_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_package_id');
        });
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn('access_level');
        });
        Schema::dropIfExists('exam_user_package');
        Schema::dropIfExists('material_user_package');
        Schema::dropIfExists('user_packages');
        Schema::dropIfExists('exam_package');
        Schema::dropIfExists('material_package');
        Schema::dropIfExists('packages');
    }
};
