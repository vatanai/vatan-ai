<?php

namespace App\Services\SmartInstagram\Automation;

/** سناریوهای آماده (پروپوزال بخش ۱۰) — همیشه با وضعیت «آزمایشی» ساخته می‌شوند. */
class AutomationTemplates
{
    public static function all(): array
    {
        return [
            'price_comment' => [
                'name' => 'کامنت «قیمت» زیر ریلز → دایرکت',
                'description' => 'پاسخ عمومی کوتاه + پاسخ خصوصی با پیشنهاد مشاوره، برچسب و فرصت فروش.',
                'icon' => 'fa-tags',
                'trigger' => 'comment_keyword',
                'keywords' => ['قیمت', 'هزینه', 'چنده', 'قیمتش'],
                'match_mode' => 'contains',
                'actions' => [
                    ['type' => 'public_reply', 'text' => '{name} عزیز، جزئیات رو در دایرکت براتون فرستادیم 🌿'],
                    ['type' => 'private_reply', 'text' => 'سلام {name} 🌿 ممنون از توجهتون. برای اینکه دقیق راهنمایی‌تون کنیم، محصولتون در چه صنفی هست؟ (پوشاک، کیف‌وکفش، زیبایی، طلا یا سایر)'],
                    ['type' => 'add_tag', 'tag' => 'پرسش قیمت'],
                    ['type' => 'create_deal', 'text' => 'پرسش قیمت از کامنت'],
                    ['type' => 'stop'],
                ],
                'guards' => ['max_per_contact_per_day' => 1, 'cooldown_minutes' => 720, 'stop_on_sensitive' => true],
            ],
            'hello_dm' => [
                'name' => '«سلام» در دایرکت → منوی نیاز',
                'description' => 'برای مشتری جدید بدون سابقه، پیام خوش‌آمد کوتاه با گزینه‌های روشن.',
                'icon' => 'fa-hand',
                'trigger' => 'first_message',
                'keywords' => [],
                'match_mode' => 'contains',
                'actions' => [
                    ['type' => 'send_dm', 'text' => "سلام {name} 🌿 به وطن خوش اومدید!\nبرای کدوم مورد کمک می‌خواید؟\n۱. عکس محصول\n۲. ویدیوی محصول\n۳. نمونه‌کار\n۴. قیمت\n۵. پشتیبانی"],
                    ['type' => 'add_tag', 'tag' => 'مشتری جدید'],
                    ['type' => 'ai_suggest'],
                ],
                'guards' => ['max_per_contact_per_day' => 1, 'cooldown_minutes' => 1440, 'stop_on_sensitive' => true],
            ],
            'complaint_human' => [
                'name' => 'شکایت یا همکاری → انسان',
                'description' => 'کلمات حساس، پاسخ خودکار را متوقف و گفتگو را اولویت‌دار به انسان می‌سپارد.',
                'icon' => 'fa-user-shield',
                'trigger' => 'dm_keyword',
                'keywords' => ['شکایت', 'همکاری', 'بازگشت وجه', 'پس دادن پول', 'کلاهبرداری'],
                'match_mode' => 'contains',
                'actions' => [
                    ['type' => 'request_human'],
                    ['type' => 'add_tag', 'tag' => 'نیازمند پیگیری انسانی'],
                    ['type' => 'create_task', 'text' => 'بررسی فوری گفتگوی {name}', 'due_hours' => 2],
                    ['type' => 'stop'],
                ],
                'guards' => ['max_per_contact_per_day' => 3, 'cooldown_minutes' => 0, 'stop_on_sensitive' => false],
            ],
            'ad_lead' => [
                'name' => 'لید از تبلیغ',
                'description' => 'ورود از تبلیغ را برچسب می‌زند، فرصت می‌سازد و پیشنهاد هوشمند تولید می‌کند.',
                'icon' => 'fa-bullhorn',
                'trigger' => 'ad_referral',
                'keywords' => [],
                'match_mode' => 'contains',
                'actions' => [
                    ['type' => 'add_tag', 'tag' => 'لید تبلیغ'],
                    ['type' => 'create_deal', 'text' => 'لید تبلیغ'],
                    ['type' => 'ai_suggest'],
                ],
                'guards' => ['max_per_contact_per_day' => 1, 'cooldown_minutes' => 1440, 'stop_on_sensitive' => true],
            ],
        ];
    }
}
