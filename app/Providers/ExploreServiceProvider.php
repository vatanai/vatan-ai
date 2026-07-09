<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\FeedCampaign;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ExploreServiceProvider extends ServiceProvider
{
    /**
     * موتور فید (اکسپلور) — سرویس‌پروایدر مستقل.
     * همه‌ی Migrationها و Routeهای مربوط به این بخش از اینجا لود می‌شوند.
     * هیچ تاثیری روی بقیه‌ی قسمت‌های پروژه ندارد (دقیقاً مثل الگوی CrmServiceProvider).
     */
    public function boot(): void
    {
        // ── Migrations ────────────────────────────────────────
        $this->loadMigrationsFrom(database_path('migrations/explore'));

        // ── Routes ────────────────────────────────────────────
        if (! $this->app->routesAreCached()) {
            require base_path('routes/explore.php');
        }

        // ── Morph Map: نگاشت نام کوتاه به مدل واقعی برای feed_content_items.content_type ──
        // عمداً از morphMap (نه enforceMorphMap) استفاده شده تا هیچ رفتار سراسری روی
        // بقیه‌ی روابط چندریختی احتمالی پروژه (خارج از موتور فید) تحمیل نشود.
        Relation::morphMap([
            'product'  => Product::class,
            'category' => Category::class,
            'campaign' => FeedCampaign::class,
        ]);
    }
}
