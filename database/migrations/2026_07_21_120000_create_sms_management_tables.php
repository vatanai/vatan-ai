<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->default('simple')->index();
            $table->string('direction', 20)->default('outgoing')->index();
            $table->string('recipient', 20)->index();
            $table->string('sender', 30)->nullable();
            $table->text('body')->nullable();
            $table->string('provider_id')->nullable()->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('provider_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        foreach ([
            'purchase_success_enabled' => '1',
            'purchase_success_template' => 'خرید شما با شماره سفارش {order_number} با موفقیت ثبت شد. سپاس از همراهی شما با وطن استودیو.',
        ] as $key => $value) {
            \Illuminate\Support\Facades\DB::table('sms_settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::table('otps', function (Blueprint $table) {
            $table->string('purpose', 30)->default('register')->after('phone')->index();
            $table->unsignedTinyInteger('attempts')->default(0)->after('used');
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'attempts']);
        });
        Schema::dropIfExists('sms_settings');
        Schema::dropIfExists('sms_messages');
    }
};
