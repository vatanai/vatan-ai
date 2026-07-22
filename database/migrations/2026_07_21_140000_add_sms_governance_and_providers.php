<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('admins', 'role')) Schema::table('admins', function (Blueprint $table) {
            $table->string('role', 30)->default('admin')->after('email')->index();
        });
        if ($first = DB::table('admins')->orderBy('id')->value('id')) DB::table('admins')->where('id', $first)->update(['role'=>'leader']);

        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('driver', 50)->default('melipayamak');
            $table->text('api_key')->nullable(); $table->string('sender')->nullable(); $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(false); $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable(); $table->text('last_error')->nullable(); $table->timestamp('last_checked_at')->nullable(); $table->timestamps();
        });
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('audience_key', 50);
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete(); $table->text('body')->nullable();
            $table->enum('status', ['draft','scheduled','sending','completed','failed'])->default('draft'); $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0); $table->unsignedInteger('sent_count')->default(0); $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete(); $table->timestamps();
        });
        DB::table('sms_providers')->insert(['name'=>'ملی‌پیامک','driver'=>'melipayamak','api_key'=>encrypt((string)config('services.melipayamak.api_key')),'sender'=>config('services.melipayamak.from'),'base_url'=>config('services.melipayamak.base_url'),'is_active'=>1,'is_default'=>1,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('sms_templates')->insert([
            ['event_key'=>'birthday','name'=>'تولد صمیمی','body'=>'{name} عزیز، تولدت مبارک! با کد {discount_code} هدیه‌ای از طرف {brand_name} برای تو داریم.','is_active'=>1,'is_default'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['event_key'=>'birthday','name'=>'تولد رسمی','body'=>'{name} گرامی، زادروزتان مبارک. با کد {discount_code} از هدیه ویژه {brand_name} استفاده کنید.','is_active'=>1,'is_default'=>0,'created_at'=>now(),'updated_at'=>now()],
            ['event_key'=>'birthday','name'=>'تولد کوتاه','body'=>'{name} عزیز تولدت مبارک! هدیه تو: {discount_code} — {brand_name}','is_active'=>1,'is_default'=>0,'created_at'=>now(),'updated_at'=>now()],
            ['event_key'=>'admin_login','name'=>'هشدار ورود مدیر','body'=>'ورود {admin_name} به داشبورد در {login_time} از IP {ip} انجام شد.','is_active'=>1,'is_default'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['event_key'=>'sms_balance_low','name'=>'هشدار شارژ پیامک','body'=>'هشدار: اعتبار {provider_name} به {balance} رسیده و کمتر از حد {threshold} است.','is_active'=>1,'is_default'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['event_key'=>'ai_balance_low','name'=>'هشدار شارژ هوش مصنوعی','body'=>'هشدار: اعتبار {provider_name} به {balance} رسیده و کمتر از حد {threshold} است.','is_active'=>1,'is_default'=>1,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
    public function down(): void
    {
        Schema::dropIfExists('sms_campaigns'); Schema::dropIfExists('sms_providers');
        if (Schema::hasColumn('admins', 'role')) Schema::table('admins', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
