<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_pages')) {
            Schema::create('site_pages', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->string('name_fa');
                $table->string('name_en')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->string('title')->nullable();
                $table->text('subtitle')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->json('meta_keywords')->nullable();
                $table->string('og_image')->nullable();
                $table->string('canonical_url', 2048)->nullable();
                $table->boolean('is_indexable')->default(true);
                $table->boolean('requires_auth')->default(false);
                $table->boolean('maintenance_mode')->default(false);
                $table->text('maintenance_message')->nullable();
                $table->json('display_settings')->nullable();
                $table->json('content_settings')->nullable();
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->timestamp('published_at')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('site_page_revisions')) {
            Schema::create('site_page_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_page_id')->constrained('site_pages')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('snapshot');
                $table->string('action', 30)->default('updated');
                $table->string('change_note')->nullable();
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
                $table->unique(['site_page_id', 'version']);
            });
        }

        $now = now();
        $defaults = [
            ['key' => 'home', 'name_fa' => 'صفحه هوم', 'name_en' => 'Home', 'title' => 'وطن؛ استودیو هوش مصنوعی', 'subtitle' => 'ایده‌ات را با هوش مصنوعی به تصویر تبدیل کن', 'status' => 'published'],
            ['key' => 'explore', 'name_fa' => 'صفحه اکسپلور', 'name_en' => 'Explore', 'title' => 'اکسپلور', 'subtitle' => 'هزاران ایده و پرامپت آماده برای ساخت تصویر و ویدیوی حرفه‌ای با هوش مصنوعی', 'status' => 'published'],
            ['key' => 'trends', 'name_fa' => 'صفحه ترند', 'name_en' => 'Trends', 'title' => 'ترندز', 'subtitle' => 'محبوب‌ترین و تازه‌ترین محصولات وطن', 'status' => 'published'],
            ['key' => 'create', 'name_fa' => 'صفحه ساخت', 'name_en' => 'Create', 'title' => 'ساخت با هوش مصنوعی', 'subtitle' => 'مدل و محصول دلخواهت را انتخاب کن و بساز', 'status' => 'published'],
            ['key' => 'profile', 'name_fa' => 'صفحه پروفایل', 'name_en' => 'Profile', 'title' => 'پروفایل من', 'subtitle' => 'مدیریت حساب و آثار ساخته‌شده', 'status' => 'published'],
            ['key' => 'articles', 'name_fa' => 'صفحه مقالات', 'name_en' => 'Articles', 'title' => 'مقالات وطن', 'subtitle' => 'آموزش‌ها و تازه‌های هوش مصنوعی', 'status' => 'draft'],
        ];

        foreach ($defaults as $page) {
            DB::table('site_pages')->updateOrInsert(
                ['key' => $page['key']],
                array_merge($page, [
                    'meta_title' => $page['title'] . ' | وطن',
                    'meta_description' => $page['subtitle'],
                    'meta_keywords' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'is_indexable' => true,
                    'requires_auth' => false,
                    'maintenance_mode' => false,
                    'display_settings' => json_encode(['show_footer' => ! in_array($page['key'], ['create', 'profile'], true), 'layout_width' => 'default', 'theme' => 'system'], JSON_UNESCAPED_UNICODE),
                    'content_settings' => json_encode(['show_page_title' => true, 'show_search' => in_array($page['key'], ['home', 'explore'], true), 'items_per_page' => 24, 'cache_ttl' => 300], JSON_UNESCAPED_UNICODE),
                    'published_at' => $page['status'] === 'published' ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_page_revisions');
        Schema::dropIfExists('site_pages');
    }
};
