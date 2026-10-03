<?php

namespace App\Services\SmartInstagram\Posts;

use App\Services\SmartInstagram\WorkspaceContext;

/** تنظیمات هوش مصنوعی «ثبت پست»: مدل و پرامپت هر بخش (در settings فضای کاری ذخیره می‌شود). */
class PostAiSettings
{
    public const SECTIONS = [
        'public_reply' => 'سه سبک پاسخ عمومی کامنت',
        'personalize' => 'پاسخ شخصی‌سازی‌شده‌ی زنده به هر کامنت',
        'opening' => 'پیام آغاز دایرکت و متن دکمه',
        'follow' => 'پیام درخواست فالو',
        'card' => 'تیتر، توضیح و دکمه‌های کارت',
    ];

    public function __construct(private readonly WorkspaceContext $context)
    {
    }

    public function defaults(): array
    {
        return [
            'model' => (string) config('smart_instagram.ai.model', 'openai/gpt-4o-mini'),
            'prompts' => [
                'public_reply' => 'سه پاسخ عمومی کوتاه (حداکثر ۱۲۰ نویسه) با سه لحن متفاوت بنویس: صمیمی، رسمی‌مؤدب، پرانرژی. هر کدام باید {name} را داشته باشد و بگوید جزئیات در دایرکت ارسال شد. حداکثر یک ایموجی. هرگز قیمت یا وعده‌ی ساختگی ننویس.',
                'personalize' => 'به کامنت مشتری یک پاسخ عمومی کوتاه، گرم و شخصی بده (حداکثر ۱۲۰ نویسه). اگر از نام کاربری یک نام کوچک فارسی قابل حدس است (مثل mohsen_shop → محسن)، با «… جان» صدا بزن؛ وگرنه بدون نام. بگو جزئیات در دایرکت ارسال شد. از سبک نمونه‌ها پیروی کن و قیمت یا اطلاعات ساختگی ننویس.',
                'opening' => 'پیام آغاز دایرکت (حداکثر ۲۰۰ نویسه) بنویس که تشکر کند و بخواهد برای دریافت لینک روی دکمه بزند؛ متن دکمه حداکثر ۲۰ نویسه.',
                'follow' => 'پیامی مؤدبانه و غیرالتماسی (حداکثر ۱۸۰ نویسه) بنویس که بگوید برای دریافت لینک ابتدا پیج را فالو کند و بعد روی «فالو کردم» بزند؛ و یک نسخه‌ی دوم برای وقتی که هنوز فالو نکرده.',
                'card' => 'برای کارت محصول یک تیتر جذاب (حداکثر ۸۰ نویسه)، یک توضیح کوتاه (حداکثر ۸۰ نویسه)، پیام قبل از کارت و حداکثر ۳ متن دکمه (هر کدام حداکثر ۲۰ نویسه) بنویس.',
            ],
        ];
    }

    public function get(): array
    {
        $saved = (array) data_get($this->context->workspace()->settings, 'post_ai', []);
        $defaults = $this->defaults();

        return [
            'model' => (string) ($saved['model'] ?? '') ?: $defaults['model'],
            'prompts' => array_merge($defaults['prompts'], array_filter((array) ($saved['prompts'] ?? []), fn ($v) => is_string($v) && trim($v) !== '')),
        ];
    }

    public function save(array $data): array
    {
        $workspace = $this->context->workspace();
        $settings = (array) $workspace->settings;
        $settings['post_ai'] = [
            'model' => trim((string) ($data['model'] ?? '')),
            'prompts' => collect((array) ($data['prompts'] ?? []))->only(array_keys(self::SECTIONS))->map(fn ($v) => mb_substr(trim((string) $v), 0, 3000))->all(),
        ];
        $workspace->forceFill(['settings' => $settings])->save();

        return $this->get();
    }
}
