<?php

namespace App\Http\Controllers;

use App\Models\GrowthEvent;
use App\Models\GrowthAttribution;
use App\Models\GrowthLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GrowthTrackingController extends Controller
{
    private const VISITOR_COOKIE = 'vtn_growth_visitor';

    public function redirect(Request $request, GrowthLink $growthLink): Response
    {
        abort_unless($growthLink->is_active, 404);

        $cookieVisitor = (string) $request->cookie(self::VISITOR_COOKIE, '');
        $visitorId = Str::isUuid($cookieVisitor) ? $cookieVisitor : (string) Str::uuid();
        $isNewVisitor = ! GrowthEvent::query()
            ->where('visitor_id', $visitorId)
            ->where('event_type', GrowthEvent::TYPE_CLICK)
            ->exists();
        $eventUuid = (string) Str::uuid();
        $device = $this->parseUserAgent((string) $request->userAgent());

        GrowthEvent::create([
            'growth_link_id' => $growthLink->id,
            'event_uuid' => $eventUuid,
            'event_type' => GrowthEvent::TYPE_CLICK,
            'visitor_id' => $visitorId,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'page_url' => $growthLink->destination_url,
            'referrer' => $request->headers->get('referer'),
            'source' => $this->queryValue($request, 'utm_source', $growthLink->channel),
            'medium' => $this->queryValue($request, 'utm_medium', 'social'),
            'campaign' => $this->queryValue($request, 'utm_campaign', $growthLink->campaign),
            'device_type' => $device['device_type'],
            'operating_system' => $device['operating_system'],
            'browser' => $device['browser'],
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'country' => $this->firstHeader($request, ['cf-ipcountry', 'x-vercel-ip-country', 'x-appengine-country']),
            'city' => $this->firstHeader($request, ['cf-ipcity', 'x-vercel-ip-city']),
            'is_new_visitor' => $isNewVisitor,
            'metadata' => ['user_agent' => Str::limit((string) $request->userAgent(), 500, '')],
            'occurred_at' => now(),
        ]);

        $destination = $this->appendQuery($growthLink->destination_url, [
            'vtn_click' => $eventUuid,
        ]);

        return redirect()->away($destination)->withCookie(cookie(
            self::VISITOR_COOKIE,
            $visitorId,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'Lax'
        ));
    }

    public function pageOpen(Request $request): Response
    {
        $data = $request->validate([
            'click_id' => ['required', 'uuid'],
            'page_url' => ['nullable', 'string', 'max:2000'],
            'referrer' => ['nullable', 'string', 'max:2000'],
        ]);

        $click = GrowthEvent::query()
            ->where('event_uuid', $data['click_id'])
            ->where('event_type', GrowthEvent::TYPE_CLICK)
            ->firstOrFail();

        GrowthEvent::firstOrCreate(
            [
                'event_type' => GrowthEvent::TYPE_PAGE_OPEN,
                'parent_event_uuid' => $click->event_uuid,
            ],
            [
                'growth_link_id' => $click->growth_link_id,
                'event_uuid' => (string) Str::uuid(),
                'visitor_id' => $click->visitor_id,
                'session_id' => $click->session_id,
                'page_url' => $data['page_url'] ?? $click->page_url,
                'referrer' => $data['referrer'] ?? null,
                'source' => $click->source,
                'medium' => $click->medium,
                'campaign' => $click->campaign,
                'device_type' => $click->device_type,
                'operating_system' => $click->operating_system,
                'browser' => $click->browser,
                'ip_hash' => $click->ip_hash,
                'country' => $click->country,
                'city' => $click->city,
                'is_new_visitor' => $click->is_new_visitor,
                'metadata' => ['tracked_by' => 'growth_tracker'],
                'occurred_at' => now(),
            ]
        );
        $this->attributeSignedInUser($request, $click);

        return response('', 204)->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function trackerScript(): Response
    {
        $endpoint = route('growth.page-open');
        $script = <<<'JS'
(function () {
  try {
    var params = new URLSearchParams(window.location.search);
    var clickId = params.get('vtn_click');
    if (!clickId) return;
    var endpoint = __ENDPOINT__;
    var query = new URLSearchParams({
      click_id: clickId,
      page_url: window.location.href.slice(0, 1900),
      referrer: (document.referrer || '').slice(0, 1900)
    });
    var beacon = new Image();
    beacon.src = endpoint + '?' + query.toString();
  } catch (error) {}
})();
JS;
        $script = str_replace('__ENDPOINT__', json_encode($endpoint, JSON_UNESCAPED_SLASHES), $script);

        return response($script, 200)->withHeaders([
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function parseUserAgent(string $userAgent): array
    {
        $mobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent) === 1;
        $operatingSystem = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            preg_match('/iPhone|iPad|iPod/i', $userAgent) === 1 => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'نامشخص',
        };
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'نامشخص',
        };

        return [
            'device_type' => $mobile ? 'mobile' : 'desktop',
            'operating_system' => $operatingSystem,
            'browser' => $browser,
        ];
    }

    private function firstHeader(Request $request, array $headers): ?string
    {
        foreach ($headers as $header) {
            $value = $request->headers->get($header);
            if ($value) {
                return Str::limit(urldecode($value), 100, '');
            }
        }

        return null;
    }

    private function queryValue(Request $request, string $key, ?string $fallback = null): ?string
    {
        $value = $request->query($key, $fallback);

        return is_scalar($value) && (string) $value !== ''
            ? Str::limit((string) $value, 255, '')
            : $fallback;
    }

    private function appendQuery(string $url, array $parameters): string
    {
        [$urlWithoutFragment, $fragment] = array_pad(explode('#', $url, 2), 2, null);
        $separator = str_contains($urlWithoutFragment, '?') ? '&' : '?';
        $result = $urlWithoutFragment.$separator.http_build_query($parameters);

        return $fragment === null ? $result : $result.'#'.$fragment;
    }

    private function attributeSignedInUser(Request $request, GrowthEvent $click): void
    {
        try {
            $userId = $request->user()?->id;
            if (! $userId || ! Schema::hasTable('growth_attributions')) {
                return;
            }

            GrowthAttribution::firstOrCreate(
                [
                    'stage' => 'landing_identified',
                    'click_event_uuid' => $click->event_uuid,
                    'user_id' => $userId,
                ],
                [
                    'growth_link_id' => $click->growth_link_id,
                    'visitor_id' => $click->visitor_id,
                    'attribution_model' => 'last_click',
                    'is_repeat' => ! $click->is_new_visitor,
                    'metadata' => ['model' => 'User'],
                    'attributed_at' => now(),
                ]
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
