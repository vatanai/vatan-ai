<?php

namespace Database\Seeders;

use App\Models\GrowthContent;
use App\Models\GrowthEvent;
use App\Models\GrowthLink;
use App\Models\MarketingCampaign;
use App\Models\MarketingContent;
use App\Models\MarketingCostEvent;
use App\Models\MarketingEvent;
use App\Models\MarketingLink;
use App\Models\MarketingOperationRun;
use App\Models\MarketingScenario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MarketingTechnologyDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('marketing_contents')) {
            $this->command?->warn('ابتدا مایگریشن‌های تکنولوژی مارکتینگ را اجرا کنید.');
            return;
        }

        $campaigns = collect([
            ['name' => 'کمپین شروع وطن', 'code' => 'demo-vatan-start', 'objective' => 'آگاهی و جذب'],
            ['name' => 'کمپین پرتره حرفه‌ای', 'code' => 'demo-portrait-pro', 'objective' => 'تعامل و لید'],
            ['name' => 'کمپین بازگشت کاربر', 'code' => 'demo-returning-users', 'objective' => 'بازگشت و خرید'],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => MarketingCampaign::query()->updateOrCreate(['code' => $data['code']], $data + ['status' => 'active', 'notes' => 'داده‌ی نمایشی برای بررسی رابط تکنولوژی مارکتینگ'])]);

        $scenarios = collect([
            ['name' => 'دریافت لینک پرتره', 'code' => 'demo-portrait-comment', 'channel' => 'instagram', 'trigger_keyword' => 'پورتره'],
            ['name' => 'پاسخ معرفی وطن', 'code' => 'demo-vatan-intro', 'channel' => 'instagram', 'trigger_keyword' => 'وطن'],
            ['name' => 'پیگیری کاربر علاقه‌مند', 'code' => 'demo-lead-followup', 'channel' => 'telegram', 'trigger_keyword' => 'راهنما'],
        ])->mapWithKeys(function (array $data): array {
            $scenario = MarketingScenario::query()->updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'channel' => $data['channel'], 'trigger_type' => 'comment_keyword', 'status' => 'active', 'active_version' => 1, 'description' => 'سناریوی نمایشی برای تست مسیر گفتگو'],
            );
            $scenario->versions()->updateOrCreate(
                ['version' => 1],
                ['status' => 'published', 'trigger_keyword' => $data['trigger_keyword'], 'public_reply' => 'پیام شما دریافت شد.', 'opening_message' => 'سلام، اطلاعات درخواستی شما آماده است.', 'opening_button_label' => 'مشاهده اطلاعات', 'followup_message' => 'اگر سوالی دارید، از همین‌جا بپرسید.', 'followup_button_label' => 'ورود به وطن', 'followup_url' => 'https://aivatan.com/app/explore', 'published_at' => now()->subDays(3)],
            );
            return [$data['code'] => $scenario];
        });

        $samples = [
            ['title' => 'پرتره استودیویی با نور نرم', 'channel' => 'instagram', 'type' => 'reel', 'campaign' => 'demo-portrait-pro', 'scenario' => 'demo-portrait-comment', 'keyword' => 'پورتره', 'days' => 1, 'impressions' => 1840, 'engagements' => 312, 'comments' => 47, 'shares' => 26, 'clicks' => 71, 'opens' => 58, 'status' => 'published'],
            ['title' => 'ساخت تصویر پروفایل حرفه‌ای', 'channel' => 'instagram', 'type' => 'post', 'campaign' => 'demo-vatan-start', 'scenario' => 'demo-vatan-intro', 'keyword' => 'وطن', 'days' => 2, 'impressions' => 2320, 'engagements' => 401, 'comments' => 63, 'shares' => 38, 'clicks' => 96, 'opens' => 79, 'status' => 'published'],
            ['title' => 'سه ایده برای عکس محصول', 'channel' => 'instagram', 'type' => 'reel', 'campaign' => 'demo-vatan-start', 'scenario' => 'demo-vatan-intro', 'keyword' => 'ایده', 'days' => 3, 'impressions' => 3180, 'engagements' => 522, 'comments' => 81, 'shares' => 54, 'clicks' => 122, 'opens' => 101, 'status' => 'published'],
            ['title' => 'قبل و بعد ادیت پرتره', 'channel' => 'instagram', 'type' => 'reel', 'campaign' => 'demo-portrait-pro', 'scenario' => 'demo-portrait-comment', 'keyword' => 'نمونه', 'days' => 4, 'impressions' => 2760, 'engagements' => 488, 'comments' => 72, 'shares' => 41, 'clicks' => 108, 'opens' => 91, 'status' => 'published'],
            ['title' => 'راهنمای انتخاب سبک تصویری', 'channel' => 'telegram', 'type' => 'post', 'campaign' => 'demo-returning-users', 'scenario' => 'demo-lead-followup', 'keyword' => 'راهنما', 'days' => 5, 'impressions' => 970, 'engagements' => 183, 'comments' => 22, 'shares' => 17, 'clicks' => 64, 'opens' => 53, 'status' => 'published'],
            ['title' => 'ویدیوی معرفی قابلیت‌های وطن', 'channel' => 'instagram', 'type' => 'reel', 'campaign' => 'demo-vatan-start', 'scenario' => 'demo-vatan-intro', 'keyword' => 'معرفی', 'days' => 6, 'impressions' => 4210, 'engagements' => 690, 'comments' => 105, 'shares' => 76, 'clicks' => 164, 'opens' => 139, 'status' => 'published'],
            ['title' => 'پیشنهاد ساخت عکس برای برند شخصی', 'channel' => 'instagram', 'type' => 'post', 'campaign' => 'demo-returning-users', 'scenario' => 'demo-lead-followup', 'keyword' => 'برند', 'days' => 7, 'impressions' => 1510, 'engagements' => 247, 'comments' => 35, 'shares' => 19, 'clicks' => 83, 'opens' => 68, 'status' => 'published'],
            ['title' => 'نمونه خروجی تبلیغاتی محصول', 'channel' => 'instagram', 'type' => 'reel', 'campaign' => 'demo-returning-users', 'scenario' => 'demo-vatan-intro', 'keyword' => 'محصول', 'days' => 8, 'impressions' => 2050, 'engagements' => 333, 'comments' => 49, 'shares' => 29, 'clicks' => 89, 'opens' => 72, 'status' => 'published'],
            ['title' => 'چطور اولین تصویر را بسازیم؟', 'channel' => 'telegram', 'type' => 'post', 'campaign' => 'demo-vatan-start', 'scenario' => 'demo-lead-followup', 'keyword' => 'شروع', 'days' => 9, 'impressions' => 740, 'engagements' => 128, 'comments' => 18, 'shares' => 12, 'clicks' => 43, 'opens' => 36, 'status' => 'published'],
            ['title' => 'دعوت به تجربه‌ی ساخت تصویر', 'channel' => 'instagram', 'type' => 'reel', 'campaign' => 'demo-returning-users', 'scenario' => 'demo-portrait-comment', 'keyword' => 'تجربه', 'days' => 10, 'impressions' => 1890, 'engagements' => 298, 'comments' => 44, 'shares' => 25, 'clicks' => 77, 'opens' => 63, 'status' => 'published'],
        ];

        foreach ($samples as $index => $sample) {
            $publishedAt = now()->subDays($sample['days'])->setTime(10 + ($index % 6), 15);
            $campaign = $campaigns[$sample['campaign']];
            $scenario = $scenarios[$sample['scenario']];
            $content = MarketingContent::query()->updateOrCreate(
                ['title' => $sample['title']],
                ['marketing_campaign_id' => $campaign->id, 'marketing_scenario_id' => $scenario->id, 'title' => $sample['title'], 'channel' => $sample['channel'], 'content_type' => $sample['type'], 'status' => $sample['status'], 'publish_at' => $publishedAt, 'published_at' => $publishedAt, 'hook' => 'اگر می‌خواهی خروجی حرفه‌ای‌تری ببینی، این نمونه را از دست نده.', 'caption' => "نمونه‌ی نمایشی شماره‌ی ".($index + 1)." برای بررسی عملکرد محتوا در وطن.", 'keyword' => $sample['keyword'], 'metadata' => ['demo' => true, 'sample_number' => $index + 1]],
            );

            $link = MarketingLink::query()->updateOrCreate(
                ['code' => 'demo-mt-link-'.($index + 1)],
                ['marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'title' => 'لینک نمونه '.$sample['keyword'], 'destination_url' => 'https://aivatan.com/app/explore?demo=marketing-'.$index, 'channel' => $sample['channel'], 'status' => 'active', 'utm' => ['demo' => 'true', 'content' => 'marketing-'.$index]],
            );
            $run = MarketingOperationRun::query()->updateOrCreate(
                ['idempotency_key' => 'demo-mt-run-'.($index + 1)],
                ['run_uuid' => (string) Str::uuid(), 'operation_type' => 'content.prepare', 'status' => $index === 8 ? 'failed' : ($index === 7 ? 'queued' : 'completed'), 'attempt' => $index === 8 ? 2 : 1, 'marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'marketing_scenario_id' => $scenario->id, 'started_at' => $publishedAt->copy()->subMinutes(8), 'finished_at' => $index === 8 || $index === 7 ? null : $publishedAt->copy()->subMinutes(3), 'error_code' => $index === 8 ? 'DEMO_TIMEOUT' : null, 'error_message' => $index === 8 ? 'خطای نمایشی برای تست تلاش مجدد' : null, 'request_payload' => ['demo' => true], 'response_payload' => $index === 8 ? null : ['demo' => true]],
            );

            $this->marketingEvent($content, $campaign, $scenario, $run, $link, 'link.clicked', $sample['channel'], $sample['clicks'], $sample['days'], 'demo-click-'.$index);
            $this->marketingEvent($content, $campaign, $scenario, $run, $link, 'link.opened', $sample['channel'], $sample['opens'], $sample['days'], 'demo-open-'.$index);
            $this->marketingEvent($content, $campaign, $scenario, $run, $link, 'comment.received', $sample['channel'], $sample['comments'], $sample['days'], 'demo-comment-'.$index);
            $this->marketingEvent($content, $campaign, $scenario, $run, $link, 'dm.received', $sample['channel'], max(1, (int) round($sample['comments'] * .45)), $sample['days'], 'demo-dm-'.$index);
            if ($index % 3 === 0) $this->marketingEvent($content, $campaign, $scenario, $run, $link, 'purchase.completed', $sample['channel'], 1, $sample['days'], 'demo-purchase-'.$index);
            MarketingCostEvent::query()->updateOrCreate(['marketing_content_id' => $content->id, 'service' => 'demo-render'], ['marketing_operation_run_id' => $run->id, 'marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'provider' => $index % 2 === 0 ? 'OpenRouter' : 'Fal', 'units' => 1 + ($index % 4), 'unit' => 'اجرا', 'unit_cost_usd' => .03 + ($index * .004), 'fx_rate_toman' => 200000, 'cost_toman' => (int) round((1 + ($index % 4)) * (.03 + ($index * .004)) * 200000), 'status' => $index === 8 ? 'needs_review' : ($index % 2 === 0 ? 'actual' : 'estimated'), 'metadata' => ['demo' => true, 'sample_number' => $index + 1], 'incurred_at' => $publishedAt]);

            if (Schema::hasTable('growth_links') && Schema::hasTable('growth_contents') && Schema::hasTable('growth_events')) {
                $growthLink = GrowthLink::query()->updateOrCreate(['slug' => 'demo-mt-'.($index + 1)], ['title' => 'رهگیری '.$sample['title'], 'destination_url' => 'https://aivatan.com/app/explore?demo=growth-'.$index, 'channel' => $sample['channel'], 'content_type' => $sample['type'], 'campaign' => $sample['campaign'], 'is_active' => true]);
                $growthData = ['growth_link_id' => $growthLink->id, 'title' => $sample['title'], 'channel' => $sample['channel'], 'content_type' => $sample['type'], 'external_id' => 'demo-mt-content-'.$index, 'external_url' => 'https://www.instagram.com/p/demo'.$index, 'status' => 'active', 'impressions' => $sample['impressions'], 'engagements' => $sample['engagements'], 'comments' => $sample['comments'], 'shares' => $sample['shares'], 'published_at' => $publishedAt, 'created_by' => null];
                if (Schema::hasColumn('growth_contents', 'likes')) $growthData['likes'] = (int) round($sample['engagements'] * .7);
                if (Schema::hasColumn('growth_contents', 'saves')) $growthData['saves'] = (int) round($sample['engagements'] * .12);
                if (Schema::hasColumn('growth_contents', 'metrics_updated_at')) $growthData['metrics_updated_at'] = now();
                GrowthContent::query()->updateOrCreate(['external_id' => 'demo-mt-content-'.$index], $growthData);
                $this->growthEvent($growthLink, GrowthEvent::TYPE_CLICK, $sample['clicks'], $sample['days']);
                $this->growthEvent($growthLink, GrowthEvent::TYPE_PAGE_OPEN, $sample['opens'], $sample['days']);
            }
        }

        $this->command?->info('۱۰ محتوای نمایشی تکنولوژی مارکتینگ با رویداد، عملیات و هزینه ثبت شد.');
    }

    private function marketingEvent($content, $campaign, $scenario, $run, $link, string $type, string $channel, int $count, int $days, string $key): void
    {
        for ($i = 0; $i < $count; $i++) {
            MarketingEvent::query()->updateOrCreate(['external_id' => $key.'-'.$i, 'event_type' => $type], ['event_uuid' => (string) Str::uuid(), 'channel' => $channel, 'processing_status' => 'processed', 'marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'marketing_scenario_id' => $scenario->id, 'marketing_operation_run_id' => $run->id, 'marketing_link_id' => $link->id, 'actor_ref' => 'demo-actor-'.$i, 'payload' => ['demo' => true], 'occurred_at' => now()->subDays($days)->subMinutes($i)]);
        }
    }

    private function growthEvent($link, string $type, int $count, int $days): void
    {
        for ($i = 0; $i < $count; $i++) {
            GrowthEvent::query()->updateOrCreate(['event_uuid' => '00000000-0000-4000-8000-'.str_pad((string) (($link->id * 1000) + ($type === GrowthEvent::TYPE_CLICK ? 0 : 500) + $i), 12, '0', STR_PAD_LEFT)], ['growth_link_id' => $link->id, 'event_type' => $type, 'visitor_id' => 'demo-visitor-'.($i % 20), 'session_id' => 'demo-session-'.$i, 'source' => 'instagram', 'medium' => 'social', 'campaign' => 'demo-marketing', 'device_type' => $i % 3 === 0 ? 'desktop' : 'mobile', 'is_new_visitor' => $i % 4 === 0, 'metadata' => ['demo' => true], 'occurred_at' => now()->subDays($days)->subMinutes($i)]);
        }
    }
}
