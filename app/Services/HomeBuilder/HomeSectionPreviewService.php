<?php

namespace App\Services\HomeBuilder;

use App\Models\HomeSection;
use App\Models\Product;

class HomeSectionPreviewService
{
    public function __construct(protected HomeSectionRenderService $renderService)
    {
    }

    public function make(array $data): array
    {
        $type = $data['type'];
        $settings = array_merge($this->defaultSettings($type), $data['settings'] ?? []);

        $section = new HomeSection([
            'type' => $type,
            'layout' => $data['layout'],
            'title_fa' => ($data['title_fa'] ?? null) ?: $this->defaultTitle($type),
            'subtitle_fa' => ($data['subtitle_fa'] ?? null) ?: 'پیش‌نمایش واقعی با محتوای فعال سایت',
            'settings' => $settings,
            'responsive' => $data['responsive'] ?? [],
            'status' => HomeSection::STATUS_DRAFT,
        ]);
        $section->id = 'preview-' . substr(md5($type . $data['layout']), 0, 8);

        $item = $this->renderService->prepare($section);

        if (isset($item['products']) && $item['products']->isEmpty()) {
            $section->settings = array_merge($settings, ['source' => 'latest']);
            $item = $this->renderService->prepare($section);
        }

        return $item;
    }

    private function defaultTitle(string $type): string
    {
        return match ($type) {
            'product_slider' => 'ترندهای امروز',
            'product_grid' => 'محصولات منتخب',
            'category_slider' => 'دسته‌بندی‌ها',
            'collection' => 'مجموعه پیشنهادی',
            default => (string) config("home_builder.types.{$type}.label", 'نمونه سکشن'),
        };
    }

    private function defaultSettings(string $type): array
    {
        return match ($type) {
            'hero' => [
                'heading' => 'ایده‌هایت را با وطن خلق کن',
                'subheading' => 'ابزارهای هوش مصنوعی و محصولات خلاقانه را یک‌جا تجربه کن.',
                'image' => $this->previewImage(),
                'cta_label' => 'شروع ساخت',
                'cta_link' => route('app.create'),
            ],
            'banner' => [
                'image' => $this->previewImage(),
                'alt_text' => 'بنر نمونه وطن',
                'link' => route('products.index'),
                'height' => 'medium',
            ],
            'text' => [
                'heading' => 'خلاقیت بدون مرز',
                'body' => 'این یک متن نمونه برای نمایش ظاهر واقعی سکشن در سایت است.',
                'align' => 'right',
            ],
            'product_slider' => [
                'source' => 'latest',
                'limit' => 8,
                'fill_empty_spaces' => false,
                'show_credit' => true,
                'show_title' => true,
                'show_category' => true,
                'show_view_all' => true,
                'view_all_link_mode' => 'auto',
                'card_aspect_ratio' => '4:5',
                'intro_scroll_mode' => 'together',
                'intro_badge' => 'استودیو وطن',
                'intro_heading' => 'از ایده تا خروجی با',
                'intro_heading_accent' => 'هوش مصنوعی',
                'intro_desc' => 'محصول مناسب را انتخاب کن و خروجی حرفه‌ای خودت را بساز.',
                'intro_steps' => "انتخاب محصول\nواردکردن اطلاعات\nدریافت خروجی",
                'intro_note' => 'ساده، سریع و قابل تنظیم',
                'intro_cta_label' => 'مشاهده محصولات',
                'intro_cta_link' => route('products.index'),
            ],
            'product_grid', 'collection' => [
                'source' => 'latest',
                'limit' => 8,
                'fill_empty_spaces' => false,
                'show_credit' => true,
                'show_view_all' => true,
                'view_all_link_mode' => 'auto',
            ],
            'category_slider' => [
                'limit' => 8,
                'products_per_tab' => 8,
                'fill_empty_spaces' => false,
                'show_view_all' => true,
                'view_all_link_mode' => 'auto',
            ],
            'spacer' => ['spacing_mode' => 'auto', 'height' => 'medium'],
            default => [],
        };
    }

    private function previewImage(): ?string
    {
        return Product::query()
            ->where('status', 'active')
            ->latest()
            ->first()
            ?->displayImageUrl();
    }
}
