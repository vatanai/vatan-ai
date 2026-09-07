<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('remember_token')->index();
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('registered_at')->index();
            }
            if (! Schema::hasColumn('users', 'login_count')) {
                $table->unsignedInteger('login_count')->default(0)->after('last_login_at');
            }
        });

        DB::table('users')->whereNull('registered_at')->update(['registered_at' => DB::raw('created_at')]);

        if (! Schema::hasTable('auth_events')) {
            Schema::create('auth_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('phone', 20)->nullable()->index();
                $table->string('event', 40)->index();
                $table->string('method', 20)->default('sms_otp')->index();
                $table->boolean('successful')->default(true)->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->string('session_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();
                $table->index(['user_id', 'event', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_events');
        Schema::table('users', function (Blueprint $table): void {
            foreach (['registered_at', 'last_login_at', 'login_count'] as $column) {
                if (Schema::hasColumn('users', $column)) $table->dropColumn($column);
            }
        });
    }
};
