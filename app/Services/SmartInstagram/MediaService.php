<?php

namespace App\Services\SmartInstagram;

use App\Jobs\SmartInstagram\FetchMessageAttachment;
use App\Models\SmartInstagram\MessageAttachment;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * پردازش رسانه و صوت (پروپوزال ۵.۴ و ۸.۳): دانلود پس‌زمینه‌ای، ذخیره در فضای خصوصی با نام غیرقابل‌حدس،
 * نمایش فقط از مسیر احرازشده‌ی پنل، و حذف واقعی فایل.
 * متن‌نگاری خودکار تا تأیید سرویس بیرونی (تصمیم ۶ پروپوزال) خاموش است؛ اپراتور می‌تواند متن صوت را دستی ثبت کند.
 */
class MediaService
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a',
        'audio/aac' => 'aac', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'application/pdf' => 'pdf',
    ];

    public function __construct(
        private readonly GatewayManager $gateways,
        private readonly OperationLogger $logger,
    ) {
    }

    public function fetch(MessageAttachment $attachment): void
    {
        if (!in_array($attachment->fetch_status, ['pending', 'retrying'], true) || !$attachment->remote_url) {
            return;
        }

        $attachment->loadMissing('message.conversation.channel');
        $channel = $attachment->message?->conversation?->channel;
        if (!$channel) {
            $attachment->forceFill(['fetch_status' => 'failed', 'fetch_error' => 'کانال پیام پیدا نشد.'])->save();

            return;
        }

        $attachment->increment('fetch_attempts');
        $result = $this->gateways->for($channel)->downloadMedia($channel, (string) $attachment->remote_url);
        $body = (string) ($result->data['body'] ?? '');
        $maxBytes = (int) config('smart_instagram.media.max_mb', 25) * 1024 * 1024;

        if (!$result->ok || $body === '') {
            $retry = $result->retryable && $attachment->fetch_attempts < 3;
            $attachment->forceFill(['fetch_status' => $retry ? 'retrying' : 'failed', 'fetch_error' => $result->message])->save();
            if ($retry) {
                FetchMessageAttachment::dispatch($attachment->id)->delay(60 * $attachment->fetch_attempts)->onQueue(config('smart_instagram.queues.media', 'default'));
            }

            return;
        }
        if (strlen($body) > $maxBytes) {
            $attachment->forceFill(['fetch_status' => 'failed', 'fetch_error' => 'حجم فایل از سقف مجاز بیشتر است.'])->save();

            return;
        }

        $mime = strtolower(trim(explode(';', (string) ($result->data['mime'] ?? 'application/octet-stream'))[0]));
        $extension = self::EXTENSIONS[$mime] ?? 'bin';
        $path = sprintf('smart-instagram/%d/%s/%s.%s', $attachment->workspace_id, now()->format('Y/m'), Str::random(40), $extension);
        Storage::disk($this->disk())->put($path, $body);

        $attachment->forceFill([
            'storage_path' => $path,
            'mime' => $mime,
            'size_bytes' => strlen($body),
            'fetch_status' => 'stored',
            'fetch_error' => null,
            'remote_url' => null, // آدرس موقت متا دیگر لازم نیست
            'transcript_status' => $attachment->type === 'audio'
                ? (config('smart_instagram.media.transcription_enabled') ? 'queued' : 'manual')
                : null,
        ])->save();
    }

    public function retry(MessageAttachment $attachment): void
    {
        $attachment->forceFill(['fetch_status' => 'pending', 'fetch_error' => null])->save();
        FetchMessageAttachment::dispatch($attachment->id)->onQueue(config('smart_instagram.queues.media', 'default'));
    }

    public function delete(MessageAttachment $attachment): void
    {
        if ($attachment->storage_path) {
            Storage::disk($this->disk())->delete($attachment->storage_path);
        }
        $attachment->forceFill(['storage_path' => null, 'remote_url' => null, 'fetch_status' => 'deleted', 'transcript' => null])->save();
        $this->logger->log('media.deleted', 'فایل پیوست #'.$attachment->id.' حذف شد.', $attachment);
    }

    public function disk(): string
    {
        return (string) config('smart_instagram.media.disk', 'local');
    }
}
