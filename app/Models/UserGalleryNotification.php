<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGalleryNotification extends Model
{
    protected $fillable = [
        'user_id',
        'user_gallery_campaign_id',
        'user_gallery_suggestion_id',
        'channel',
        'status',
        'consent_checked',
        'payload',
        'error_message',
        'sent_at',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_checked' => 'boolean',
            'payload' => 'array',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function campaign(): BelongsTo { return $this->belongsTo(UserGalleryCampaign::class, 'user_gallery_campaign_id'); }
    public function suggestion(): BelongsTo { return $this->belongsTo(UserGallerySuggestion::class, 'user_gallery_suggestion_id'); }
}
