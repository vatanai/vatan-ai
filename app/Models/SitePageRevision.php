<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitePageRevision extends Model
{
    protected $fillable = ['site_page_id', 'version', 'snapshot', 'action', 'change_note', 'admin_id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
