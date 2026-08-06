<?php

namespace Database\Seeders;

use App\Models\FeedCampaign;
use App\Models\FeedSetting;
use App\Models\FeedSurface;
use App\Models\Product;
use Illuminate\Database\Seeder;
use RuntimeException;

class ExploreLayoutDemoSeeder extends Seeder
{
    public function run(): void
    {
        $templates = Product::query()
            ->where('status', 'active')
            ->where('slug', 'not like', 'explore-layout-demo-%')
            ->with('categories')
            ->get();

        if ($templates->isEmpty()) {
            throw new RuntimeException('برای ساخت محصولات نمایشی اکسپلور حداقل یک محصول فعال لازم است.');
        }

        $names = [
            'پرتره سینمایی شب', 'پوستر فروش ویژه تابستان', 'تصویر خانوادگی مینیمال', 'ریلز معرفی محصول',
            'عکس تبلیغاتی کافه', 'آواتار سه‌بعدی فانتزی', 'دعوت‌نامه متحرک جشن', 'ویدیوی سفر رویایی',
            'عکاسی استودیویی پوشاک', 'پرتره کلاسیک سیاه‌وسفید', 'پوستر افتتاحیه فروشگاه', 'ویدیوی معرفی برند',
            'تصویر کودک در دنیای قصه', 'عکس حرفه‌ای غذای رستوران', 'کاور شبکه اجتماعی', 'تیزر مناسبتی نوروز',
            'پرتره هنری حیوان خانگی', 'تصویر معماری مدرن', 'پوستر رویداد فناوری', 'ویدیوی فشن خیابانی',
        ];
        $videoPaths = [
            'products/samples/ai-wedding-invitation-video-1.mp4',
            'products/samples/ai-cinematic-short-video-1.mp4',
            'products/samples/ai-instagram-reels-ad-1.mp4',
            'products/samples/ai-instagram-reels-ad-2.mp4',
        ];
        $tilePatterns = [
            ['1x1'], ['1x1', '2x1'], ['1x1', '1x2'], ['1x1', '2x2'],
        ];

        foreach ($names as $index => $name) {
            $number = $index + 1;
            $slug = sprintf('explore-layout-demo-%02d', $number);
            $template = $templates[$index % $templates->count()];
            $isVideo = $number % 4 === 0;

            $product = Product::withTrashed()->where('slug', $slug)->first();
            // حذف نرم‌شده‌ی مدیر یک تصمیم محتوایی است و نباید با اجرای Seeder
            // دوباره به فهرست عمومی برگردد.
            if ($product?->trashed()) {
                continue;
            }
            if (! $product) {
                $product = $template->replicate();
                $product->product_code = Product::generateUniqueProductCode();
            }

            $product->forceFill([
                'name_fa' => $name,
                'name_en' => 'Explore Layout Demo ' . $number,
                'slug' => $slug,
                'status' => $product->exists ? $product->status : 'active',
                'media_type' => $isVideo ? 'video' : 'photo',
                'preview_video_url' => $isVideo ? $videoPaths[$index % count($videoPaths)] : null,
                'is_new' => $number <= 9,
                'is_featured' => $number % 5 === 0,
                'is_trending' => $number % 3 === 0,
                'tags' => array_values(array_unique(array_merge((array) $template->tags, [
                    'دمو اکسپلور',
                    $isVideo ? 'ویدیو' : 'تصویر',
                    $number % 2 === 0 ? 'مناسبتی' : 'دسته‌بندی ویژه',
                ]))),
                'explore_tiles' => $tilePatterns[$index % count($tilePatterns)],
                'created_at' => now()->subHours($index),
                'updated_at' => now(),
            ])->save();

            $categoryIds = $template->categories->pluck('id')->all();
            if ($categoryIds !== []) {
                $product->categories()->sync($categoryIds);
            }
        }

        $campaigns = [
            ['جشنواره پرتره و آواتار', 'products/thumbnails/ai-fashion-portrait.avif', '/app/explore?q=پرتره', 95],
            ['ویژه محصولات ویدیویی', 'products/thumbnails/ai-cinematic-short-video.jpg', '/app/explore?q=ویدیو', 90],
            ['کمپین کسب‌وکار و فروش', 'products/thumbnails/sample-poster-ai.jpg', '/app/explore?q=کسب‌وکار', 85],
            ['حال‌وهوای جشن و مناسبت', 'products/thumbnails/sample-wedding-style.jpg', '/app/explore?q=مناسبتی', 80],
        ];

        foreach ($campaigns as [$title, $image, $link, $weight]) {
            FeedCampaign::query()->updateOrCreate(
                ['title_fa' => $title],
                [
                    'image' => $image,
                    'link' => $link,
                    'weight' => $weight,
                    'start_at' => null,
                    'end_at' => null,
                    'is_active' => true,
                ]
            );
        }

        $surface = FeedSurface::query()->firstOrCreate(
            ['key' => 'explore'],
            ['title_fa' => 'اکسپلور', 'is_active' => true]
        );
        FeedSetting::query()
            ->where('feed_surface_id', $surface->id)
            ->where('is_active_version', true)
            ->update(['campaign_ratio' => 20]);
    }
}
