<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sms_messages')) Schema::create('sms_messages', function (Blueprint $table) {
            $table->id(); $table->string('type', 40)->default('simple')->index();
            $table->string('direction', 20)->default('outgoing')->index(); $table->string('recipient', 20)->index();
            $table->string('sender', 30)->nullable(); $table->text('body')->nullable(); $table->string('provider_id')->nullable()->index();
            $table->string('status', 30)->default('pending')->index(); $table->text('provider_status')->nullable();
            $table->json('metadata')->nullable(); $table->timestamp('scheduled_at')->nullable(); $table->timestamp('sent_at')->nullable(); $table->timestamps();
        });
        if (!Schema::hasTable('sms_settings')) Schema::create('sms_settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->timestamps();
        });
        if (Schema::hasTable('otps') && !Schema::hasColumn('otps', 'purpose')) Schema::table('otps', function (Blueprint $table) {
            $table->string('purpose', 30)->default('register')->after('phone')->index();
        });
        if (Schema::hasTable('otps') && !Schema::hasColumn('otps', 'attempts')) Schema::table('otps', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('used');
        });

        if (!Schema::hasTable('sms_templates')) Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 60)->index();
            $table->string('name');
            $table->text('body');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            $table->index(['event_key', 'is_active', 'is_default']);
        });

        $now = now();
        if (DB::table('sms_templates')->count() === 0) DB::table('sms_templates')->insert([
            ['event_key'=>'login_success','name'=>'ورود استاندارد','body'=>'{name} عزیز، ورود موفق به حساب وطن استودیو در {login_time} انجام شد.','is_active'=>1,'is_default'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['event_key'=>'registration_success','name'=>'خوش‌آمدگویی','body'=>'{name} عزیز، به وطن استودیو خوش آمدید. {gift_credits} اعتبار هدیه برای شما فعال شد.','is_active'=>1,'is_default'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['event_key'=>'purchase_success','name'=>'خرید استاندارد','body'=>'{name} عزیز، خرید شما با شماره {order_number} با موفقیت ثبت شد. موجودی: {balance} اعتبار.','is_active'=>1,'is_default'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['event_key'=>'order_completed','name'=>'آماده شدن سفارش','body'=>'{name} عزیز، سفارش {order_number} آماده شد و اکنون قابل مشاهده است.','is_active'=>1,'is_default'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['event_key'=>'refund_success','name'=>'بازگشت اعتبار','body'=>'{name} عزیز، {amount} اعتبار سفارش {order_number} بازگردانده شد. موجودی جدید: {balance}.','is_active'=>1,'is_default'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['event_key'=>'credit_low','name'=>'هشدار موجودی کم','body'=>'{name} عزیز، موجودی اعتبار شما به {balance} رسیده است. برای ادامه استفاده حساب خود را شارژ کنید.','is_active'=>1,'is_default'=>1,'created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('sms_settings')->updateOrInsert(['key'=>'admin_test_phone'], ['value'=>'','created_at'=>$now,'updated_at'=>$now]);
        DB::table('sms_settings')->updateOrInsert(['key'=>'credit_low_threshold'], ['value'=>'100','created_at'=>$now,'updated_at'=>$now]);
    }

    public function down(): void { Schema::dropIfExists('sms_templates'); }
};
