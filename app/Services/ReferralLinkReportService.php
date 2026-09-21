<?php

namespace App\Services;

use App\Models\ReferralConversion;
use App\Models\ReferralLink;
use App\Models\ReferralReward;
use App\Models\ReferralVisit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReferralLinkReportService
{
    public function linksFor(User $user): Collection
    {
        return ReferralLink::query()
            ->where('inviter_id', $user->id)
            ->with('product:id,name_fa,name_en,slug,thumbnail,cover,sample_outputs')
            ->latest('id')
            ->limit(30)
            ->get();
    }

    /** @return array{link:?ReferralLink, report:array<string,mixed>} */
    public function resolve(User $user, ?string $slug): array
    {
        if ($slug === null || $slug === '' || $slug === 'profile') {
            return [
                'link' => null,
                'report' => $this->profileReport($user),
            ];
        }

        $link = ReferralLink::query()
            ->where('slug', $slug)
            ->where('inviter_id', $user->id)
            ->with('product:id,name_fa,name_en,slug,thumbnail,cover,sample_outputs')
            ->firstOrFail();

        return [
            'link' => $link,
            'report' => $this->linkReport($link),
        ];
    }

    /** @return array<string,mixed> */
    public function linkReport(ReferralLink $link): array
    {
        $link->loadMissing('product');
        $visits = $link->visits()->get(['id', 'visitor_token', 'visited_at']);
        $conversions = $link->conversions()->with(['rewards', 'invitee.planPurchases'])->get();
        $rewards = ReferralReward::query()
            ->where('user_id', $link->inviter_id)
            ->whereHas('conversion', fn ($query) => $query->where('link_id', $link->id))
            ->get(['amount', 'status', 'direction', 'currency', 'reward_type', 'created_at']);

        $title = $link->product?->name_fa ?: ($link->product?->name_en ?: 'لینک اختصاصی رفرال');

        return [
            'title' => $title,
            'subtitle' => $link->product ? 'گزارش عملکرد لینک محصول' : 'گزارش عملکرد لینک رفرال',
            'share_url' => route('referral.link', $link->slug),
            'image' => $link->product?->displayImageUrl(),
            'status' => $link->isActive() ? 'فعال' : 'غیرفعال',
            'status_class' => $link->isActive() ? 'active' : 'inactive',
            'slug' => $link->slug,
            'stats' => $this->stats($visits, $conversions, $rewards),
            'series' => $this->series($visits, $conversions),
            'recent' => $this->recentConversions($conversions),
            'updated_at' => $this->updatedAt($link, $visits, $conversions, $rewards),
            'is_profile' => false,
        ];
    }

    /** @return array<string,mixed> */
    private function profileReport(User $user): array
    {
        $visits = ReferralVisit::query()
            ->where('inviter_id', $user->id)
            ->get(['id', 'visitor_token', 'visited_at']);
        $conversions = ReferralConversion::query()
            ->where('inviter_id', $user->id)
            ->with(['rewards', 'invitee.planPurchases'])
            ->get();
        $rewards = ReferralReward::query()
            ->where('user_id', $user->id)
            ->whereIn('reward_type', ['inviter_reward', 'purchase_reward', 'purchase_commission', 'purchase_commission_reversal'])
            ->get(['amount', 'status', 'direction', 'currency', 'reward_type', 'created_at']);

        return [
            'title' => 'لینک عمومی دعوت من',
            'subtitle' => 'گزارش عملکرد کد دعوت شخصی',
            'share_url' => $user->referral_url,
            'image' => null,
            'status' => 'فعال',
            'status_class' => 'active',
            'slug' => 'profile',
            'stats' => $this->stats($visits, $conversions, $rewards),
            'series' => $this->series($visits, $conversions),
            'recent' => $this->recentConversions($conversions),
            'updated_at' => $this->updatedAt($user, $visits, $conversions, $rewards),
            'is_profile' => true,
        ];
    }

    /** @param Collection<int,ReferralVisit> $visits @param Collection<int,ReferralConversion> $conversions @param Collection<int,ReferralReward> $rewards */
    private function stats(Collection $visits, Collection $conversions, Collection $rewards): array
    {
        $clicks = $visits->count();
        $unique = $visits->pluck('visitor_token')->filter()->unique()->count();
        $registrations = $conversions->count();
        $qualified = $conversions->whereIn('status', ['qualified', 'under_review'])->count();
        $purchases = $conversions->filter(fn ($conversion) => $conversion->invitee?->planPurchases?->contains('status', 'completed'))->count();
        $images = $conversions->whereNotNull('first_image_at')->count();
        $paid = $this->rewardTotal($rewards, 'paid');
        $pending = $this->rewardTotal($rewards, 'pending');
        $conversionRate = $clicks > 0 ? round(($registrations / $clicks) * 100, 1) : 0;

        return [
            'clicks' => $clicks,
            'unique' => $unique,
            'registrations' => $registrations,
            'qualified' => $qualified,
            'purchases' => $purchases,
            'images' => $images,
            'paid' => $paid,
            'pending' => $pending,
            'conversion_rate' => $conversionRate,
        ];
    }

    /** @param Collection<int,ReferralReward> $rewards */
    private function rewardTotal(Collection $rewards, string $status): int
    {
        return (int) $rewards
            ->where('status', $status)
            ->sum(fn ($reward) => ($reward->direction ?? 'credit') === 'debit'
                ? -((int) $reward->amount)
                : (int) $reward->amount);
    }

    /** @param Collection<int,ReferralVisit> $visits @param Collection<int,ReferralConversion> $conversions */
    private function series(Collection $visits, Collection $conversions): array
    {
        $days = collect(range(13, 0))->map(function (int $offset) use ($visits, $conversions): array {
            $date = Carbon::today()->subDays($offset);
            $key = $date->toDateString();

            return [
                'label' => $date->format('m/d'),
                'clicks' => $visits->filter(fn ($visit) => $visit->visited_at?->toDateString() === $key)->count(),
                'registrations' => $conversions->filter(fn ($conversion) => $conversion->created_at?->toDateString() === $key)->count(),
            ];
        });

        $max = max(1, (int) $days->max('clicks'));

        return $days->map(fn (array $day): array => $day + [
            'height' => max(8, (int) round(($day['clicks'] / $max) * 100)),
        ])->values()->all();
    }

    /** @param Collection<int,ReferralConversion> $conversions */
    private function recentConversions(Collection $conversions): array
    {
        return $conversions
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function (ReferralConversion $conversion): array {
                $reward = $conversion->rewards->sortByDesc('created_at')->first();
                $status = match ($conversion->status) {
                    'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                    'under_review' => ['label' => 'در حال بررسی', 'class' => 'warning'],
                    default => $reward?->status === 'paid'
                        ? ['label' => 'پاداش پرداخت شد', 'class' => 'success']
                        : ['label' => 'دعوت معتبر', 'class' => 'success'],
                };

                return [
                    'label' => $status['label'],
                    'class' => $status['class'],
                    'date' => $conversion->created_at?->format('Y/m/d'),
                ];
            })
            ->values()
            ->all();
    }

    private function updatedAt(object $owner, Collection $visits, Collection $conversions, Collection $rewards): ?Carbon
    {
        return collect([
            $owner->updated_at,
            $visits->max('visited_at'),
            $conversions->max('updated_at'),
            $rewards->max('created_at'),
        ])->filter()->map(fn ($date) => $date instanceof Carbon ? $date : Carbon::parse($date))->sortDesc()->first();
    }
}
