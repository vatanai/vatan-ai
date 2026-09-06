<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * این migration ایمن برای اجرای مجدد (idempotent) نوشته شده است:
     * - هر ستون فقط در صورت نبودنش اضافه می‌شود (رفع خطای Duplicate column name).
     * - قبل از افزودن یونیک ایندکس روی plan_code/slug، مقدارهای خالی/تکراری
     *   با کد و اسلاگ یکتا پر می‌شوند (رفع خطای Duplicate entry '' for key ...).
     * - ایندکس یونیک فقط در صورت نبودنش ساخته می‌شود.
     * این‌طوری صرف‌نظر از اینکه اجرای قبلی روی Production تا کجا پیش رفته
     * و نصفه مونده، اجرای دوباره‌ی migrate --force بدون خطا کامل می‌شود.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'plan_code')) {
                $table->string('plan_code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('plans', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'short_description')) {
                $table->text('short_description')->nullable()->after('tokens');
            }
            if (!Schema::hasColumn('plans', 'description')) {
                $table->longText('description')->nullable()->after('short_description');
            }
            if (!Schema::hasColumn('plans', 'icon')) {
                $table->string('icon')->default('fa-solid fa-gem')->after('image_path');
            }
            if (!Schema::hasColumn('plans', 'card_color')) {
                $table->string('card_color')->default('#a07af5')->after('icon');
            }
            if (!Schema::hasColumn('plans', 'badge_text')) {
                $table->string('badge_text')->nullable()->after('card_color');
            }
            if (!Schema::hasColumn('plans', 'tags')) {
                $table->json('tags')->nullable()->after('badge_text');
            }
            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('plans', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('features');
            }
            if (!Schema::hasColumn('plans', 'status')) {
                $table->enum('status', ['draft', 'active', 'inactive'])->default('active')->after('sort_order');
            }
            if (!Schema::hasColumn('plans', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('status');
            }

            // حذف فیلد قدیمی is_active برای جلوگیری از همپوشانی با وضعیت status
            if (Schema::hasColumn('plans', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        // پر کردن مقدارهای خالی/تکراری plan_code و slug پیش از افزودن ایندکس یونیک
        $usedCodes = DB::table('plans')->whereNotNull('plan_code')->where('plan_code', '!=', '')->pluck('plan_code')->flip()->all();
        $usedSlugs = DB::table('plans')->whereNotNull('slug')->where('slug', '!=', '')->pluck('slug')->flip()->all();

        $rows = DB::table('plans')
            ->where(function ($q) {
                $q->whereNull('plan_code')->orWhere('plan_code', '')
                  ->orWhereNull('slug')->orWhere('slug', '');
            })
            ->get();

        foreach ($rows as $row) {
            $update = [];

            if (empty($row->plan_code)) {
                do {
                    $code = 'PLN-' . strtoupper(Str::random(5));
                } while (isset($usedCodes[$code]));
                $usedCodes[$code] = true;
                $update['plan_code'] = $code;
            }

            if (empty($row->slug)) {
                $base = Str::slug($row->name ?: ('plan-' . $row->id)) ?: ('plan-' . $row->id);
                $slug = $base;
                $i = 2;
                while (isset($usedSlugs[$slug])) {
                    $slug = $base . '-' . $i++;
                }
                $usedSlugs[$slug] = true;
                $update['slug'] = $slug;
            }

            if (!empty($update)) {
                DB::table('plans')->where('id', $row->id)->update($update);
            }
        }

        // اضافه کردن ایندکس یونیک فقط در صورت نبودنش (رفع خطای Duplicate key name)
        if (!$this->indexExists('plans', 'plans_plan_code_unique')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unique('plan_code');
            });
        }
        if (!$this->indexExists('plans', 'plans_slug_unique')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $columns = [
                'plan_code',
                'slug',
                'short_description',
                'description',
                'icon',
                'card_color',
                'badge_text',
                'tags',
                'features',
                'sort_order',
                'status',
                'version',
            ];
            $existing = array_filter($columns, fn ($c) => Schema::hasColumn('plans', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('image_path');
            }
        });
    }

    /**
     * بررسی وجود ایندکس روی جدول (سازگار با MySQL)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::connection()->getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, $table, $indexName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }
};
