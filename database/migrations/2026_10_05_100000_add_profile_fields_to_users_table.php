<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::User->value)->index()->after('email');
            $table->string('status')->default(UserStatus::Active->value)->index()->after('role');
            $table->string('phone', 30)->nullable()->after('status');
            $table->string('address')->nullable()->after('phone');
            $table->string('institution')->nullable()->after('address');
            $table->string('avatar_path')->nullable()->after('institution');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();

            foreach (['last_login_at', 'avatar_path', 'institution', 'address', 'phone', 'status', 'role'] as $column) {
                $table->dropColumn($column);
            }
        });
    }
};
