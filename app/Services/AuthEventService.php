<?php

namespace App\Services;

use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class AuthEventService
{
    public function record(Request $request, string $event, ?User $user = null, ?string $phone = null, bool $successful = true, array $metadata = [], string $method = 'sms_otp'): void
    {
        try {
            AuthEvent::query()->create([
                'user_id' => $user?->id,
                'phone' => $phone ?: $user?->phone,
                'event' => $event,
                'method' => $method,
                'successful' => $successful,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'metadata' => $metadata ?: null,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
